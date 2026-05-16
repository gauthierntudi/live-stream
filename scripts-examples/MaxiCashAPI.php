<?php
/**
 * Client API MaxiCash - Version corrigée selon la documentation officielle
 */

class MaxiCashAPI {
    private $merchantId;
    private $merchantPassword;
    private $apiMode;
    private $baseUrl;
    
    // URLs selon la documentation officielle
    private $urls = [
        'test' => [
            'mobile' => 'https://webapi.maxicashapp.com/Integration/PayNowSync',
            'card' => 'https://webapi.maxicashapp.com/Integration/PayCreditCard'
        ],
        'live' => [
            'mobile' => 'https://webapi.maxicashapp.com/Integration/PayNowSync',
            'card' => 'https://webapi.maxicashapp.com/Integration/PayCreditCard'
        ]
    ];
    
    // Types de paiement selon la documentation
    private $payTypes = [
        'maxicash' => 0,
        'airtel' => 1,
        'mpesa' => 2,
        'orange' => 3,
        'rakka' => 51
    ];
    
    public function __construct($merchantId, $merchantPassword, $apiMode = 'test') {
        $this->merchantId = $merchantId;
        $this->merchantPassword = $merchantPassword;
        $this->apiMode = $apiMode;
        $this->baseUrl = $this->urls[$apiMode];
    }
    
    /**
     * Paiement Mobile Money selon la documentation
     */
    public function payNowSync($paymentData) {
        $url = $this->baseUrl['mobile'];

        // Format exact selon la documentation
        $requestBody = [
            'RequestData' => [
                'Amount' => (string)($paymentData['amount'] * 100), // Montant en centimes selon la doc
                'Reference' => $paymentData['reference'],
                'Telephone' => $this->formatPhoneNumber($paymentData['phone'])
            ],
            'MerchantID' => $this->merchantId,
            'MerchantPassword' => $this->merchantPassword,
            'PayType' => $this->getPayType($paymentData['method']),
            'CurrencyCode' => $paymentData['currency']
        ];

        $this->logRequest('PayNowSync', $requestBody);

        $result = $this->makeApiCall($url, $requestBody);

        // Si timeout, considérer comme "pending" plutôt qu'échec
        if (!$result['success'] && isset($result['error']) &&
            (stripos($result['error'], 'timeout') !== false ||
             stripos($result['error'], 'timed out') !== false)) {

            $this->logError("Timeout détecté - Transaction en attente de validation mobile");

            return [
                'success' => true,
                'pending' => true,
                'awaiting_mobile_validation' => true,
                'reference' => $paymentData['reference'],
                'message' => 'Paiement en attente. Veuillez valider sur votre téléphone mobile.',
                'instructions' => 'Composez *150*00# (Orange) ou vérifiez votre notification mobile pour finaliser le paiement.'
            ];
        }

        return $result;
    }
    
    /**
     * Paiement par carte de crédit selon la documentation
     */
    public function payCreditCard($paymentData) {
        $url = $this->baseUrl['card'];

        // Validation des données de carte
        $validationErrors = $this->validateCardData($paymentData);
        if (!empty($validationErrors)) {
            return [
                'success' => false,
                'error' => 'Données de carte invalides: ' . implode(', ', $validationErrors)
            ];
        }

        // Ajouter le numéro d'achat aux URLs de retour pour le tracking
        $purchaseParam = '?purchase=' . urlencode(explode('_', $paymentData['reference'])[0]);

        // Pour les paiements par carte, si aucun numéro n'est fourni, utiliser un numéro par défaut
        // MaxiCash exige ce champ même pour les paiements par carte
        $phone = $this->formatPhoneNumber($paymentData['phone']);
        if (empty($phone)) {
            // Utiliser un numéro par défaut valide si aucun numéro n'est disponible
            $phone = '243000000000';
            $this->logError("No phone provided for card payment, using default", ['default' => $phone]);
        }

        // Format exact selon la documentation
        $requestBody = [
            'PayType' => 'MaxiCash',
            'MerchantID' => $this->merchantId,
            'MerchantPassword' => $this->merchantPassword,
            'Amount' => (string)($paymentData['amount'] * 100), // Montant en centimes selon la doc
            'Currency' => $paymentData['currency'],
            'Telephone' => $phone,
            'Language' => $paymentData['language'] ?? 'fr',
            'Reference' => $paymentData['reference'],
            'SuccessURL' => $paymentData['success_url'] . $purchaseParam . '&status=success',
            'FailureURL' => $paymentData['failure_url'] . $purchaseParam . '&status=failed',
            'CancelURL' => ($paymentData['cancel_url'] ?? $paymentData['failure_url']) . $purchaseParam . '&status=cancelled',
            'NotifyURL' => $paymentData['notify_url'] ?? '',
            'FirstName' => $paymentData['first_name'] ?? '',
            'LastName' => $paymentData['last_name'] ?? '',
            'Email' => $paymentData['email'] ?? '',
            'cData' => [
                'cDate' => $this->formatCardExpiry($paymentData['card_expiry']),
                'cNumber' => str_replace(' ', '', $paymentData['card_number']),
                'vCVV' => $paymentData['card_cvv']
            ]
        ];
        
        // Log sans données sensibles
        $logRequest = $requestBody;
        $logRequest['cData'] = [
            'cDate' => $paymentData['card_expiry'],
            'cNumber' => 'XXXX-XXXX-XXXX-' . substr($paymentData['card_number'], -4),
            'vCVV' => 'XXX'
        ];
        $this->logRequest('PayCreditCard', $logRequest);
        
        return $this->makeApiCall($url, $requestBody);
    }
    
    /**
     * Appel API avec gestion d'erreurs
     */
    private function makeApiCall($url, $data, $retries = 3) {
        $attempt = 0;
        
        while ($attempt < $retries) {
            $attempt++;
            
            $ch = curl_init();
            
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 420, // 7 minutes comme dans la doc
                CURLOPT_CONNECTTIMEOUT => 30,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Accept: application/json',
                    'User-Agent: MaxiCash-PHP-Client/2.0'
                ],
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 3
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                $this->logError("cURL Error (attempt $attempt): $error");
                if ($attempt >= $retries) {
                    return [
                        'success' => false,
                        'error' => 'Erreur de connexion: ' . $error
                    ];
                }
                sleep(1);
                continue;
            }
            
            if ($httpCode !== 200) {
                $this->logError("HTTP Error (attempt $attempt): $httpCode", ['response' => $response]);
                if ($attempt >= $retries) {
                    return [
                        'success' => false,
                        'error' => "Erreur HTTP: $httpCode",
                        'response' => $response
                    ];
                }
                sleep(1);
                continue;
            }
            
            break;
        }
        
        $decodedResponse = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logError('JSON decode error: ' . json_last_error_msg(), ['response' => $response]);
            return [
                'success' => false,
                'error' => 'Erreur de décodage JSON: ' . json_last_error_msg(),
                'raw_response' => $response
            ];
        }
        
        $this->logResponse($decodedResponse);
        
        return $this->parseMaxiCashResponse($decodedResponse);
    }
    
    /**
     * Parse la réponse MaxiCash selon la documentation
     */
    private function parseMaxiCashResponse($response) {
        $status = strtolower($response['ResponseStatus'] ?? 'unknown');
        $transactionId = $response['TransactionID'] ?? null;
        $responseData = $response['ResponseData'] ?? null;

        // Déterminer le statut normalisé pour compatibilité sync
        $normalizedStatus = $status;
        $normalizedResult = ($status === 'success' && strtolower($responseData) === 'success') ? 'Success' : $responseData;

        switch ($status) {
            case 'success':
                // Pour les cartes de crédit, Success signifie redirection nécessaire
                $redirectUrl = $responseData;

                if (!empty($redirectUrl) && filter_var($redirectUrl, FILTER_VALIDATE_URL)) {
                    return [
                        'success' => true,
                        'pending' => true,
                        'requires_redirect' => true,
                        'redirect_url' => $redirectUrl,
                        'transaction_id' => $transactionId,
                        'reference' => $response['Reference'] ?? null,
                        'message' => 'Redirection vers MaxiCash pour finaliser le paiement',
                        'status' => $normalizedStatus,
                        'result' => $normalizedResult,
                        'full_response' => $response
                    ];
                }

                // Paiement mobile money réussi immédiatement
                return [
                    'success' => true,
                    'transaction_id' => $transactionId,
                    'reference' => $response['Reference'] ?? null,
                    'status' => $normalizedStatus,
                    'result' => $normalizedResult,
                    'message' => $response['ResponseDesc'] ?? 'Paiement réussi',
                    'data' => $responseData,
                    'full_response' => $response
                ];
                
            case 'pending':
                // Pour les cartes de crédit, ResponseData contient l'URL de redirection
                $redirectUrl = $response['ResponseData'] ?? null;

                return [
                    'success' => true,
                    'pending' => true,
                    'requires_redirect' => !empty($redirectUrl),
                    'redirect_url' => $redirectUrl,
                    'transaction_id' => $response['TransactionID'] ?? null,
                    'reference' => $response['Reference'] ?? null,
                    'message' => $response['ResponseDesc'] ?? 'Paiement en attente',
                    'status' => $status,
                    'result' => $responseData,
                    'full_response' => $response
                ];

            case 'failed':
            case 'error':
            default:
                $errorMessage = $this->getErrorMessage($response);

                // Si le message d'erreur contient "timeout", traiter comme pending
                if (stripos($errorMessage, 'timeout') !== false ||
                    stripos($errorMessage, 'timed out') !== false) {

                    $this->logError("Timeout dans réponse MaxiCash - Transaction pending");

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
                        'full_response' => $response
                    ];
                }

                return [
                    'success' => false,
                    'error' => $errorMessage,
                    'message' => $response['ResponseDesc'] ?? '',
                    'status' => $status,
                    'result' => $responseData,
                    'transaction_id' => $response['TransactionID'] ?? null,
                    'full_response' => $response
                ];
        }
    }
    
    /**
     * Messages d'erreur traduits
     */
    private function getErrorMessage($response) {
        $errorMessage = $response['ResponseError'] ?? $response['ResponseDesc'] ?? 'Transaction échouée';
        
        $errorMappings = [
            'Invalid Registration Details' => 'Identifiants marchand invalides',
            'Telephone No is required' => 'Numéro de téléphone requis',
            'Insufficient funds' => 'Fonds insuffisants',
            'Transaction timeout' => 'Délai d\'attente dépassé',
            'Invalid card' => 'Informations de carte invalides',
            'Card expired' => 'Carte expirée',
            'Declined' => 'Paiement refusé par la banque',
            'Invalid amount' => 'Montant invalide',
            'Invalid currency' => 'Devise non supportée'
        ];
        
        foreach ($errorMappings as $key => $translation) {
            if (stripos($errorMessage, $key) !== false) {
                return $translation;
            }
        }
        
        return $errorMessage;
    }
    
    /**
     * Validation des données de carte
     */
    private function validateCardData($paymentData) {
        $errors = [];
        
        $cardNumber = str_replace(' ', '', $paymentData['card_number'] ?? '');
        if (empty($cardNumber) || !$this->isValidCardNumber($cardNumber)) {
            $errors[] = 'Numéro de carte invalide';
        }
        
        $expiry = $paymentData['card_expiry'] ?? '';
        if (empty($expiry) || !$this->isValidExpiry($expiry)) {
            $errors[] = 'Date d\'expiration invalide';
        }
        
        $cvv = $paymentData['card_cvv'] ?? '';
        if (empty($cvv) || !preg_match('/^\d{3,4}$/', $cvv)) {
            $errors[] = 'CVV invalide';
        }
        
        $email = $paymentData['email'] ?? '';
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email valide requis';
        }
        
        return $errors;
    }
    
    /**
     * Validation du numéro de carte avec algorithme de Luhn
     */
    private function isValidCardNumber($cardNumber) {
        if (!preg_match('/^\d{13,19}$/', $cardNumber)) {
            return false;
        }
        
        $sum = 0;
        $alternate = false;
        
        for ($i = strlen($cardNumber) - 1; $i >= 0; $i--) {
            $digit = intval($cardNumber[$i]);
            
            if ($alternate) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit = ($digit % 10) + 1;
                }
            }
            
            $sum += $digit;
            $alternate = !$alternate;
        }
        
        return ($sum % 10) === 0;
    }
    
    /**
     * Validation de la date d'expiration
     */
    private function isValidExpiry($expiry) {
        if (!preg_match('/^(\d{2})\/(\d{4})$/', $expiry, $matches)) {
            return false;
        }
        
        $month = intval($matches[1]);
        $year = intval($matches[2]);
        
        if ($month < 1 || $month > 12) {
            return false;
        }
        
        $expiryDate = new DateTime("$year-$month-01");
        $expiryDate->modify('last day of this month');
        
        return $expiryDate >= new DateTime();
    }
    
    /**
     * Formatage de la date d'expiration pour MaxiCash
     */
    private function formatCardExpiry($expiry) {
        // MaxiCash attend le format MM/YYYY
        if (preg_match('/^(\d{2})\/(\d{4})$/', $expiry, $matches)) {
            return $matches[1] . '/' . $matches[2];
        }
        return $expiry;
    }
    
    /**
     * Formatage du numéro de téléphone pour MaxiCash
     */
    private function formatPhoneNumber($phone) {
        $this->logError("Phone formatting - Original", ['phone' => $phone]);

        // Gérer les valeurs null ou vides
        if (empty($phone)) {
            $this->logError("Phone formatting - Empty or null phone number");
            return '';
        }

        // Nettoyer le numéro (supprimer espaces, tirets, parenthèses, +)
        $cleanPhone = preg_replace('/[^\d]/', '', (string)$phone);
        $this->logError("Phone formatting - Cleaned", ['phone' => $cleanPhone]);
        
        // Format attendu: 243 + 9 chiffres = 243XXXXXXXXX (12 chiffres au total)
        if (strlen($cleanPhone) === 12 && substr($cleanPhone, 0, 3) === '243') {
            // Déjà au bon format: 243846516270
            $this->logError("Phone formatting - Correct 12 digits format", ['phone' => $cleanPhone]);
            return $cleanPhone;
        } elseif (strlen($cleanPhone) === 9) {
            // Format: 846516270 → 243846516270
            $formatted = '243' . $cleanPhone;
            $this->logError("Phone formatting - Added 243 prefix", ['original' => $cleanPhone, 'formatted' => $formatted]);
            return $formatted;
        } elseif (strlen($cleanPhone) === 10 && substr($cleanPhone, 0, 1) === '0') {
            // Format: 0846516270 → 243846516270
            $formatted = '243' . substr($cleanPhone, 1);
            $this->logError("Phone formatting - Removed 0 and added 243", ['original' => $cleanPhone, 'formatted' => $formatted]);
            return $formatted;
        } else {
            // Autres cas non gérés
            $this->logError("Phone formatting - Unhandled format", ['phone' => $cleanPhone]);
            return $cleanPhone; // Retourne le numéro original nettoyé
        }
    }
    
    /**
     * Type de paiement
     */
    private function getPayType($method) {
        return $this->payTypes[strtolower($method)] ?? 0;
    }
    
    /**
     * Validation générale des données de paiement
     */
    public function validatePaymentData($paymentData) {
        $errors = [];
        
        if (empty($paymentData['amount']) || $paymentData['amount'] <= 0) {
            $errors[] = 'Montant invalide';
        }
        
        if (empty($paymentData['reference'])) {
            $errors[] = 'Référence manquante';
        }
        
        if (empty($paymentData['currency']) || !in_array($paymentData['currency'], ['USD', 'CDF'])) {
            $errors[] = 'Devise invalide (USD ou CDF uniquement)';
        }
        
        // Validation du téléphone
        $phone = $this->formatPhoneNumber($paymentData['phone'] ?? '');
        if (strlen($phone) !== 12 || !preg_match('/^243[0-9]{9}$/', $phone)) {
            $errors[] = 'Numéro de téléphone invalide (format: 243XXXXXXXXX)';
            $this->logError("Phone validation failed", ['original' => $paymentData['phone'], 'formatted' => $phone]);
        }
        
        // Validation spécifique selon la méthode
        if ($paymentData['method'] === 'card') {
            $cardErrors = $this->validateCardData($paymentData);
            $errors = array_merge($errors, $cardErrors);
        } else {
            // Validation du numéro selon l'opérateur
            if (!$this->isValidPhoneForMethod($phone, $paymentData['method'])) {
                $errors[] = 'Numéro de téléphone invalide pour ' . ucfirst($paymentData['method']);
            }
        }
        
        return $errors;
    }
    
    /**
     * Valide le numéro selon l'opérateur
     */
    private function isValidPhoneForMethod($phone, $method) {
        // Le numéro doit être au format 243XXXXXXXXX (12 chiffres)
        if (strlen($phone) !== 12 || substr($phone, 0, 3) !== '243') {
            return false;
        }
        
        // Extraire les 9 derniers chiffres (après 243)
        $localNumber = substr($phone, 3);
        
        // Extraire le préfixe (2 premiers chiffres du numéro local)
        $prefix = substr($localNumber, 0, 2);
        
        switch (strtolower($method)) {
            case 'airtel':
                // Airtel: 99, 98, 97, 96, 95, 94, 93, 92, 91, 90
                return in_array($prefix, ['99', '98', '97', '96', '95', '94', '93', '92', '91', '90']);
            case 'mpesa':
                // M-Pesa: 81, 82, 83, 80
                return in_array($prefix, ['81', '82', '83', '80']);
            case 'orange':
                // Orange: 84, 85, 86, 87, 88, 89
                return in_array($prefix, ['84', '85', '86', '87', '88', '89']);
            case 'maxicash':
            case 'rakka':
            default:
                // Tous les autres (8X ou 9X)
                return in_array(substr($prefix, 0, 1), ['8', '9']);
        }
    }
    
    /**
     * Vérifie le statut d'un paiement par référence
     */
    public function checkPaymentStatusByReference($reference, $transactionId = '') {
        $url = ($this->apiMode === 'test')
            ? 'https://webapi-test.maxicashapp.com/Integration/CheckPaymentStatusByReference'
            : 'https://webapi.maxicashapp.com/Integration/CheckPaymentStatusByReference';

        $requestBody = [
            'MerchantID' => $this->merchantId,
            'MerchantPassword' => $this->merchantPassword,
            'Reference' => $reference,
            'TransactionID' => $transactionId
        ];

        $this->logRequest('CheckPaymentStatusByReference', $requestBody);

        $result = $this->makeApiCall($url, $requestBody);

        return $result;
    }

    /**
     * Logging des requêtes
     */
    private function logRequest($endpoint, $data) {
        // Log sans données sensibles pour les cartes
        $logData = $data;
        if (isset($logData['cData'])) {
            $logData['cData'] = [
                'cDate' => $data['cData']['cDate'],
                'cNumber' => 'XXXX-XXXX-XXXX-' . substr($data['cData']['cNumber'], -4),
                'vCVV' => 'XXX'
            ];
        }
        error_log("MaxiCash Request [$endpoint]: " . json_encode($logData));
    }
    
    /**
     * Logging des réponses
     */
    private function logResponse($data) {
        error_log("MaxiCash Response: " . json_encode($data));
    }
    
    /**
     * Logging des erreurs
     */
    private function logError($message, $context = []) {
        error_log("MaxiCash Error: $message" . (!empty($context) ? ' - ' . json_encode($context) : ''));
    }
}
?>