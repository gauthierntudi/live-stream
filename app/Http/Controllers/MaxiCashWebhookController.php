<?php

namespace App\Http\Controllers;

use App\Models\Donation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class MaxiCashWebhookController extends Controller
{
    public function __invoke(Request $request): Response|RedirectResponse
    {
        if ($request->isMethod('GET')) {
            return $this->handleBrowserReturn($request);
        }

        return $this->handleServerCallback($request);
    }

    private function handleBrowserReturn(Request $request): RedirectResponse
    {
        $purchase = $request->query('purchase') ?? $request->query('donation');
        $status = $request->query('status') ?? $request->query('ResponseStatus');

        if (! is_string($purchase) || $purchase === '' || ! is_string($status) || $status === '') {
            return redirect()->route('donations.index');
        }

        $publicId = explode('_', $purchase, 2)[0];
        $donation = Donation::query()->where('public_id', $publicId)->first();

        if (! $donation) {
            return redirect()->route('donations.index');
        }

        return match (strtolower($status)) {
            'success', 'paid' => redirect()->route('donations.success', ['public_id' => $donation->public_id]),
            'failed', 'error' => redirect()->route('donations.failure', ['public_id' => $donation->public_id]),
            'pending', 'cancelled' => redirect()->route('donations.pending', ['public_id' => $donation->public_id]),
            default => redirect()->route('donations.index'),
        };
    }

    private function handleServerCallback(Request $request): Response
    {
        $secret = config('maxicash.webhook_secret');
        if (is_string($secret) && $secret !== '') {
            $token = $request->bearerToken();
            if ($token !== $secret) {
                return response('Unauthorized', 401);
            }
        }

        $rawBody = $request->getContent();
        /** @var array<string, mixed>|null $decoded */
        $decoded = $rawBody !== '' ? json_decode($rawBody, true) : null;
        $jsonData = is_array($decoded) ? $decoded : [];

        $reference = $request->input('Reference') ?? ($jsonData['Reference'] ?? null);
        $transactionId = $request->input('TransactionID') ?? ($jsonData['TransactionID'] ?? null);
        $status = $request->input('ResponseStatus') ?? ($jsonData['ResponseStatus'] ?? null);
        $amount = $request->input('Amount') ?? ($jsonData['Amount'] ?? null);

        /** @var array<string, mixed> $payload */
        $payload = array_merge($jsonData, array_filter([
            'Reference' => $reference,
            'TransactionID' => $transactionId,
            'ResponseStatus' => $status,
            'Amount' => $amount,
        ], fn ($v) => $v !== null));

        if (! is_string($reference) || $reference === '' || ! is_string($status) || $status === '') {
            Log::warning('maxicash.webhook.missing_reference', ['payload' => $payload]);

            return response()->json(['success' => true, 'message' => 'Webhook reçu'], 200);
        }

        $publicId = explode('_', $reference, 2)[0];
        $donation = Donation::query()->where('public_id', $publicId)->first();

        if (! $donation) {
            Log::warning('maxicash.webhook.donation_not_found', ['reference' => $reference]);

            return response()->json(['success' => false, 'message' => 'Don non trouvé'], 404);
        }

        $tid = is_scalar($transactionId) ? (string) $transactionId : null;

        return match (strtolower($status)) {
            'success', 'paid' => $this->markPaid($donation, $tid, $payload),
            'pending' => $this->markPending($donation, $tid, $payload),
            'failed', 'error', 'cancelled' => $this->markFailedOrCancelled($donation, $status, $tid, $payload),
            default => $this->ackUnknown($donation, $payload),
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function markPaid(Donation $donation, ?string $tid, array $payload): Response
    {
        if ($donation->status !== Donation::STATUS_PAID) {
            $donation->update([
                'status' => Donation::STATUS_PAID,
                'maxicash_transaction_id' => $tid ?? $donation->maxicash_transaction_id,
                'provider_payload' => $payload,
            ]);
        }

        return response()->json(['success' => true, 'message' => 'Don traité avec succès'], 200);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function markPending(Donation $donation, ?string $tid, array $payload): Response
    {
        $donation->update([
            'status' => Donation::STATUS_PENDING,
            'maxicash_transaction_id' => $tid ?? $donation->maxicash_transaction_id,
            'provider_payload' => $payload,
        ]);

        return response()->json(['success' => true, 'message' => 'Don en attente'], 200);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function markFailedOrCancelled(Donation $donation, string $status, ?string $tid, array $payload): Response
    {
        $donation->update([
            'status' => strtolower($status) === 'cancelled' ? Donation::STATUS_CANCELLED : Donation::STATUS_FAILED,
            'maxicash_transaction_id' => $tid ?? $donation->maxicash_transaction_id,
            'provider_payload' => $payload,
        ]);

        return response()->json(['success' => true, 'message' => 'Statut mis à jour'], 200);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function ackUnknown(Donation $donation, array $payload): Response
    {
        $donation->update(['provider_payload' => $payload]);

        return response()->json(['success' => true, 'message' => 'Webhook reçu'], 200);
    }
}
