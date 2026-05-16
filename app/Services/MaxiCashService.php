<?php

namespace App\Services;

use App\Models\Donation;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MaxiCashService
{
    /** @var array<string, int> */
    private const PAY_TYPES = [
        'maxicash' => 0,
        'airtel' => 1,
        'mpesa' => 2,
        'orange' => 3,
        'rakka' => 51,
    ];

    /**
     * @param  array<string, mixed>  $input  donor_phone, donor_email, language, first_name, last_name
     * @return array{
     *     redirect_url: ?string,
     *     raw: ?array<string, mixed>,
     *     error?: string,
     *     pending?: bool,
     *     paid_immediate?: bool,
     *     awaiting_mobile_validation?: bool,
     *     message?: string,
     *     instructions?: string,
     *     reference?: string
     * }
     */
    public function initiatePayment(Donation $donation, array $input): array
    {
        $merchantId = (string) config('maxicash.merchant_id');
        $merchantPassword = (string) config('maxicash.merchant_password');
        $mode = config('maxicash.api_mode') === 'live' ? 'live' : 'test';

        if ($merchantId === '' || $merchantPassword === '') {
            return $this->emptyResult('Configuration du paiement incomplète côté serveur.');
        }

        $reference = $donation->public_id.'_'.time();
        $method = strtolower((string) $donation->payment_method);

        [$defaultFirst, $defaultLast] = $this->splitDonorName((string) ($donation->donor_name ?? ''));

        /** @var array<string, mixed> $paymentData */
        $paymentData = [
            'amount' => (float) $donation->amount,
            'currency' => strtoupper((string) $donation->currency),
            'reference' => $reference,
            'method' => $method,
            'phone' => (string) ($input['donor_phone'] ?? ''),
            'language' => (string) ($input['language'] ?? 'fr'),
            'email' => (string) ($donation->donor_email ?? $input['donor_email'] ?? ''),
            'first_name' => (string) ($input['first_name'] ?? $defaultFirst),
            'last_name' => (string) ($input['last_name'] ?? $defaultLast),
        ];

        $validationErrors = $this->validatePaymentData($paymentData);
        if ($validationErrors !== []) {
            return $this->emptyResult('Données de paiement invalides : '.implode(' ', $validationErrors));
        }

        $callbackUrl = rtrim((string) route('webhooks.maxicash', [], true), '/');
        $purchase = rawurlencode(strtok($reference, '_') ?: $donation->public_id);
        $paymentData['success_url'] = $callbackUrl.'?purchase='.$purchase.'&status=success';
        $paymentData['failure_url'] = $callbackUrl.'?purchase='.$purchase.'&status=failed';
        $paymentData['cancel_url'] = $callbackUrl.'?purchase='.$purchase.'&status=cancelled';
        $paymentData['notify_url'] = $callbackUrl;

        $donation->update([
            'maxicash_reference' => $reference,
            'status' => Donation::STATUS_PROCESSING,
        ]);

        $result = $method === 'card'
            ? $this->payEntryWeb($merchantId, $merchantPassword, $paymentData)
            : $this->payNowSync($merchantId, $merchantPassword, $paymentData);

        return $this->mapInitiationResult($donation, $result);
    }

    /**
     * @param  array<string, mixed>  $paymentData
     * @return array<string, mixed>
     */
    private function payNowSync(string $merchantId, string $merchantPassword, array $paymentData): array
    {
        $url = (string) config('maxicash.mobile_url');
        $requestBody = [
            'RequestData' => [
                'Amount' => (string) ((int) round($paymentData['amount'] * 100)),
                'Reference' => $paymentData['reference'],
                'Telephone' => $this->formatPhoneNumber($paymentData['phone']),
            ],
            'MerchantID' => $merchantId,
            'MerchantPassword' => $merchantPassword,
            'PayType' => $this->getPayType((string) $paymentData['method']),
            'CurrencyCode' => $paymentData['currency'],
        ];

        $this->logRequest('PayNowSync', $requestBody);
        $result = $this->makeApiCall(
            $url,
            $requestBody,
            max(30, (int) config('maxicash.paynow_timeout', 420))
        );

        if (! ($result['success'] ?? false) && isset($result['error']) && is_string($result['error'])) {
            $err = strtolower($result['error']);
            if (str_contains($err, 'timeout')
                || str_contains($err, 'timed out')
                || str_contains($err, 'curl error 28')
                || str_contains($err, 'operation timed out')) {
                return [
                    'success' => true,
                    'pending' => true,
                    'awaiting_mobile_validation' => true,
                    'reference' => $paymentData['reference'],
                    'message' => 'Paiement en attente. Veuillez valider sur votre téléphone mobile.',
                    'instructions' => 'Composez *150*00# (Orange) ou vérifiez votre notification mobile pour finaliser le paiement.',
                ];
            }
        }

        return $result;
    }

    /**
     * Paiement carte : PayEntryWeb (étape 1) → redirection payentryweb?logid=… (étape 2).
     * La carte est saisie sur la page hébergée MaxiCash, pas sur notre formulaire.
     *
     * @param  array<string, mixed>  $paymentData
     * @return array<string, mixed>
     */
    private function payEntryWeb(string $merchantId, string $merchantPassword, array $paymentData): array
    {
        $url = $this->resolvePayEntryWebUrl();

        $phone = $this->formatPhoneNumber($paymentData['phone']);
        if ($phone === '') {
            $phone = '243000000000';
            Log::warning('maxicash.card_default_phone', ['reference' => $paymentData['reference']]);
        }

        $requestBody = [
            'PayType' => 'MaxiCash',
            'MerchantID' => $merchantId,
            'MerchantPassword' => $merchantPassword,
            'Amount' => (string) ((int) round($paymentData['amount'] * 100)),
            'Currency' => $this->mapGatewayCurrency((string) $paymentData['currency']),
            'Telephone' => $phone,
            'Language' => $paymentData['language'] ?? 'fr',
            'Reference' => $paymentData['reference'],
            'SuccessURL' => $paymentData['success_url'],
            'FailureURL' => $paymentData['failure_url'],
            'CancelURL' => $paymentData['cancel_url'],
            'NotifyURL' => $paymentData['notify_url'] ?? '',
        ];

        $email = trim((string) ($paymentData['email'] ?? ''));
        if ($email !== '') {
            $requestBody['Email'] = $email;
        }

        $this->logRequest('PayEntryWeb', array_merge($requestBody, ['_endpoint' => $url]));

        $raw = $this->postJson($url, $requestBody, max(15, (int) config('maxicash.card_timeout', 120)));

        return $this->parsePayEntryWebResponse($raw);
    }

    /**
     * @param  array<string, mixed>  $response
     * @return array<string, mixed>
     */
    private function parsePayEntryWebResponse(array $response): array
    {
        $status = strtolower((string) ($response['ResponseStatus'] ?? 'unknown'));

        if ($status === 'success') {
            $logId = $response['LogID'] ?? $response['ResponseData'] ?? null;
            if (is_scalar($logId) && (string) $logId !== '') {
                $logId = (string) $logId;
                $checkoutBase = rtrim((string) config('maxicash.gateway_checkout_url'), '/');
                $redirectUrl = $checkoutBase.'?logid='.rawurlencode($logId);

                return [
                    'success' => true,
                    'pending' => true,
                    'requires_redirect' => true,
                    'redirect_url' => $redirectUrl,
                    'transaction_id' => $response['TransactionID'] ?? null,
                    'reference' => $response['Reference'] ?? null,
                    'message' => 'Redirection vers la page sécurisée MaxiCash pour finaliser le paiement par carte.',
                    'status' => $status,
                    'result' => $logId,
                    'full_response' => $response,
                ];
            }
        }

        return $this->parseMaxiCashResponse($response);
    }

    private function mapGatewayCurrency(string $currency): string
    {
        return match (strtoupper($currency)) {
            'USD' => 'maxiDollar',
            'ZAR' => 'maxiRand',
            default => $currency,
        };
    }

    private function resolvePayEntryWebUrl(): string
    {
        $url = (string) config('maxicash.pay_entry_web_url');

        if ($url === '' || str_contains($url, 'PayCreditCard')) {
            $legacy = (string) config('maxicash.card_url');
            if ($legacy !== '' && ! str_contains($legacy, 'PayCreditCard')) {
                $url = $legacy;
            }
        }

        if ($url === '' || str_contains($url, 'PayCreditCard')) {
            if (str_contains((string) config('maxicash.card_url'), 'PayCreditCard')) {
                Log::warning('maxicash.misconfigured_card_url', [
                    'hint' => 'MAXICASH_CARD_URL pointe vers PayCreditCard ; utilisation forcée de PayEntryWeb.',
                    'card_url' => config('maxicash.card_url'),
                ]);
            }

            $url = config('maxicash.api_mode') === 'live'
                ? 'https://webapi.maxicashapp.com/Integration/PayEntryWeb'
                : 'https://webapi-test.maxicashapp.com/Integration/PayEntryWeb';
        }

        return $url;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function postJson(string $url, array $data, int $timeoutSeconds): array
    {
        $result = $this->makeApiCall($url, $data, $timeoutSeconds);

        if (isset($result['full_response']) && is_array($result['full_response'])) {
            return $result['full_response'];
        }

        if (isset($result['error'])) {
            return [
                'ResponseStatus' => 'failed',
                'ResponseError' => (string) $result['error'],
            ];
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    private function makeApiCall(string $url, array $data, int $timeoutSeconds): array
    {
        $timeoutSeconds = max(5, $timeoutSeconds);
        $connectTimeout = max(2, (int) config('maxicash.connect_timeout', 30));
        $maxAttempts = max(1, (int) config('maxicash.http_attempts', 1));

        if (function_exists('set_time_limit')) {
            @set_time_limit(min(480, $timeoutSeconds * $maxAttempts + 45));
        }

        $attempt = 0;
        $lastError = 'Erreur de connexion au prestataire de paiement';

        while ($attempt < $maxAttempts) {
            $attempt++;
            try {
                $response = Http::timeout($timeoutSeconds)
                    ->connectTimeout($connectTimeout)
                    ->withHeaders([
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                        'User-Agent' => 'MaxiCash-PHP-Client/2.0 (Laravel)',
                    ])
                    ->post($url, $data);

                if (! $response->successful()) {
                    Log::warning('maxicash.http_error', ['status' => $response->status(), 'body' => $response->body()]);
                    $lastError = 'Erreur HTTP '.$response->status();
                    if ($attempt >= $maxAttempts) {
                        return [
                            'success' => false,
                            'error' => $lastError,
                            'raw_response' => $response->body(),
                        ];
                    }
                    usleep(200_000);

                    continue;
                }

                /** @var array<string, mixed>|null $decoded */
                $decoded = $response->json();
                if (! is_array($decoded)) {
                    return [
                        'success' => false,
                        'error' => 'Réponse du prestataire invalide (format attendu).',
                        'raw_response' => $response->body(),
                    ];
                }
                $this->logResponse($decoded);

                return $this->parseMaxiCashResponse($decoded);
            } catch (\Throwable $e) {
                Log::warning('maxicash.request_exception', ['message' => $e->getMessage(), 'attempt' => $attempt]);
                $lastError = 'Erreur de connexion : '.$e->getMessage();
                if ($attempt >= $maxAttempts) {
                    return ['success' => false, 'error' => $lastError];
                }
                usleep(200_000);
            }
        }

        return ['success' => false, 'error' => $lastError];
    }

    /**
     * @param  array<string, mixed>  $response
     * @return array<string, mixed>
     */
    private function parseMaxiCashResponse(array $response): array
    {
        $status = strtolower((string) ($response['ResponseStatus'] ?? 'unknown'));
        $transactionId = $response['TransactionID'] ?? null;
        $responseData = $response['ResponseData'] ?? null;
        $normalizedResult = ($status === 'success' && is_string($responseData) && strtolower($responseData) === 'success')
            ? 'Success'
            : $responseData;

        return match ($status) {
            'success' => $this->parseSuccessBranch($response, $responseData, $transactionId, $normalizedResult),
            'pending' => [
                'success' => true,
                'pending' => true,
                'requires_redirect' => is_string($responseData) && $responseData !== '' && filter_var($responseData, FILTER_VALIDATE_URL),
                'redirect_url' => is_string($responseData) && filter_var($responseData, FILTER_VALIDATE_URL) ? $responseData : null,
                'transaction_id' => $response['TransactionID'] ?? null,
                'reference' => $response['Reference'] ?? null,
                'message' => $response['ResponseDesc'] ?? 'Paiement en attente',
                'status' => $status,
                'result' => $responseData,
                'full_response' => $response,
            ],
            default => $this->parseFailedBranch($response, $status, $responseData, $transactionId),
        };
    }

    /**
     * @param  array<string, mixed>  $response
     * @return array<string, mixed>
     */
    private function parseSuccessBranch(array $response, mixed $responseData, mixed $transactionId, mixed $normalizedResult): array
    {
        $redirectUrl = is_string($responseData) ? $responseData : null;
        if (is_string($redirectUrl) && filter_var($redirectUrl, FILTER_VALIDATE_URL)) {
            return [
                'success' => true,
                'pending' => true,
                'requires_redirect' => true,
                'redirect_url' => $redirectUrl,
                'transaction_id' => $transactionId,
                'reference' => $response['Reference'] ?? null,
                        'message' => 'Redirection pour finaliser le paiement sur la page sécurisée.',
                'status' => 'success',
                'result' => $normalizedResult,
                'full_response' => $response,
            ];
        }

        return [
            'success' => true,
            'transaction_id' => $transactionId,
            'reference' => $response['Reference'] ?? null,
            'status' => 'success',
            'result' => $normalizedResult,
            'message' => $response['ResponseDesc'] ?? 'Paiement réussi',
            'data' => $responseData,
            'full_response' => $response,
        ];
    }

    /**
     * @param  array<string, mixed>  $response
     * @return array<string, mixed>
     */
    private function parseFailedBranch(array $response, string $status, mixed $responseData, mixed $transactionId): array
    {
        $errorMessage = $this->getErrorMessage($response);
        if (stripos($errorMessage, 'timeout') !== false || stripos($errorMessage, 'timed out') !== false) {
            return [
                'success' => true,
                'pending' => true,
                'awaiting_mobile_validation' => true,
                'reference' => $response['Reference'] ?? null,
                'message' => 'Paiement en attente de validation mobile',
                'instructions' => 'Veuillez valider le paiement sur votre téléphone mobile.',
                'status' => 'pending',
                'result' => 'Pending',
                'transaction_id' => $response['TransactionID'] ?? null,
                'full_response' => $response,
            ];
        }

        return [
            'success' => false,
            'error' => $errorMessage,
            'message' => $response['ResponseDesc'] ?? '',
            'status' => $status,
            'result' => $responseData,
            'transaction_id' => $transactionId,
            'full_response' => $response,
        ];
    }

    /**
     * @param  array<string, mixed>  $response
     */
    private function getErrorMessage(array $response): string
    {
        $errorMessage = (string) ($response['ResponseError'] ?? $response['ResponseDesc'] ?? 'Transaction échouée');
        $errorMappings = [
            'Invalid Registration Details' => 'Identifiants marchand invalides',
            'Telephone No is required' => 'Numéro de téléphone requis',
            'Insufficient funds' => 'Fonds insuffisants',
            'Transaction timeout' => 'Délai d\'attente dépassé',
            'Invalid card' => 'Informations de carte invalides',
            'Card expired' => 'Carte expirée',
            'Declined' => 'Paiement refusé par la banque',
            'Invalid amount' => 'Montant invalide',
            'Invalid currency' => 'Devise non supportée',
        ];
        foreach ($errorMappings as $key => $translation) {
            if (stripos($errorMessage, $key) !== false) {
                return $translation;
            }
        }

        return $errorMessage;
    }

    private function formatPhoneNumber(string $phone): string
    {
        if ($phone === '') {
            return '';
        }
        $cleanPhone = preg_replace('/[^\d]/', '', $phone) ?? '';
        if (strlen($cleanPhone) === 12 && str_starts_with($cleanPhone, '243')) {
            return $cleanPhone;
        }
        if (strlen($cleanPhone) === 9) {
            return '243'.$cleanPhone;
        }
        if (strlen($cleanPhone) === 10 && str_starts_with($cleanPhone, '0')) {
            return '243'.substr($cleanPhone, 1);
        }

        return $cleanPhone;
    }

    private function getPayType(string $method): int
    {
        return self::PAY_TYPES[$method] ?? 0;
    }

    /**
     * @param  array<string, mixed>  $paymentData
     * @return list<string>
     */
    private function validatePaymentData(array $paymentData): array
    {
        $errors = [];
        if (empty($paymentData['amount']) || (float) $paymentData['amount'] <= 0) {
            $errors[] = 'Montant invalide';
        }
        if (empty($paymentData['reference'])) {
            $errors[] = 'Référence manquante';
        }
        $currency = (string) ($paymentData['currency'] ?? '');
        if ($currency === '' || $currency !== 'USD') {
            $errors[] = 'Devise invalide (USD uniquement).';
        }
        $phone = $this->formatPhoneNumber((string) ($paymentData['phone'] ?? ''));
        if (strlen($phone) !== 12 || ! preg_match('/^243[0-9]{9}$/', $phone)) {
            $errors[] = 'Numéro de téléphone invalide (format RDC : 243XXXXXXXXX).';
        } elseif (strtolower((string) $paymentData['method']) !== 'card' && ! $this->isValidPhoneForMethod($phone, (string) $paymentData['method'])) {
            $errors[] = 'Numéro de téléphone invalide pour '.ucfirst((string) $paymentData['method']).'.';
        }
        if (strtolower((string) $paymentData['method']) === 'card') {
            $email = trim((string) ($paymentData['email'] ?? ''));
            if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'E-mail valide requis pour le paiement par carte.';
            }
        }

        return $errors;
    }

    private function isValidPhoneForMethod(string $phone, string $method): bool
    {
        if (strlen($phone) !== 12 || ! str_starts_with($phone, '243')) {
            return false;
        }
        $localNumber = substr($phone, 3);
        $prefix = substr($localNumber, 0, 2);
        $methodLower = strtolower($method);

        return match ($methodLower) {
            'airtel' => in_array($prefix, ['99', '98', '97', '96', '95', '94', '93', '92', '91', '90'], true),
            'mpesa' => in_array($prefix, ['81', '82', '83', '80'], true),
            'orange' => in_array($prefix, ['84', '85', '86', '87', '88', '89'], true),
            default => in_array(substr($prefix, 0, 1), ['8', '9'], true),
        };
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function splitDonorName(string $name): array
    {
        $name = trim($name);
        if ($name === '') {
            return ['Donateur', '—'];
        }
        $parts = preg_split('/\s+/', $name, 2);

        return [
            (string) ($parts[0] ?? 'Donateur'),
            (string) ($parts[1] ?? $parts[0] ?? '—'),
        ];
    }

    /**
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    private function mapInitiationResult(Donation $donation, array $result): array
    {
        $raw = isset($result['full_response']) && is_array($result['full_response'])
            ? $result['full_response']
            : $result;

        if (! ($result['success'] ?? false)) {
            $donation->update([
                'status' => Donation::STATUS_FAILED,
                'provider_payload' => $raw,
            ]);

            return [
                'redirect_url' => null,
                'raw' => $raw,
                'error' => (string) ($result['error'] ?? 'Paiement refusé'),
            ];
        }

        if (isset($result['redirect_url']) && is_string($result['redirect_url']) && $result['redirect_url'] !== '') {
            $donation->update([
                'maxicash_transaction_id' => is_scalar($result['transaction_id'] ?? null)
                    ? (string) $result['transaction_id']
                    : $donation->maxicash_transaction_id,
                'provider_payload' => $raw,
            ]);

            return [
                'redirect_url' => $result['redirect_url'],
                'raw' => $raw,
                'pending' => true,
                'reference' => is_string($result['reference'] ?? null) ? $result['reference'] : null,
            ];
        }

        if (($result['awaiting_mobile_validation'] ?? false) || (($result['pending'] ?? false) && ($result['requires_redirect'] ?? false) === false)) {
            $donation->update([
                'maxicash_transaction_id' => is_scalar($result['transaction_id'] ?? null)
                    ? (string) $result['transaction_id']
                    : $donation->maxicash_transaction_id,
                'provider_payload' => $raw,
            ]);

            return [
                'redirect_url' => null,
                'raw' => $raw,
                'pending' => true,
                'awaiting_mobile_validation' => (bool) ($result['awaiting_mobile_validation'] ?? false),
                'message' => is_string($result['message'] ?? null) ? $result['message'] : null,
                'instructions' => is_string($result['instructions'] ?? null) ? $result['instructions'] : null,
                'reference' => is_string($result['reference'] ?? null) ? $result['reference'] : null,
            ];
        }

        $donation->update([
            'status' => Donation::STATUS_PAID,
            'maxicash_transaction_id' => is_scalar($result['transaction_id'] ?? null)
                ? (string) $result['transaction_id']
                : $donation->maxicash_transaction_id,
            'provider_payload' => $raw,
        ]);

        return [
            'redirect_url' => null,
            'raw' => $raw,
            'paid_immediate' => true,
            'reference' => is_string($result['reference'] ?? null) ? $result['reference'] : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function logRequest(string $endpoint, array $data): void
    {
        Log::info("maxicash.request.$endpoint", $data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function logResponse(array $data): void
    {
        Log::info('maxicash.response', $data);
    }

    /**
     * @return array{redirect_url: null, raw: null, error: string}
     */
    private function emptyResult(string $message): array
    {
        return [
            'redirect_url' => null,
            'raw' => null,
            'error' => $message,
        ];
    }
}
