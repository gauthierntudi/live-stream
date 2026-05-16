<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDonationRequest;
use App\Models\Donation;
use App\Services\MaxiCashService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;

class DonationController extends Controller
{
    public function __construct(
        private MaxiCashService $maxiCash
    ) {}

    public function index(): View
    {
        $iconsDir = resource_path('img/icons');
        $paymentMethods = collect(config('payment_methods.methods', []))
            ->filter(fn (array $m) => isset($m['id'], $m['icon']) && File::exists($iconsDir.DIRECTORY_SEPARATOR.$m['icon']))
            ->values()
            ->all();

        return view('donations.index', compact('paymentMethods'));
    }

    public function store(StoreDonationRequest $request): RedirectResponse|JsonResponse
    {
        $donation = Donation::query()->create([
            'donor_name' => $request->validated('donor_name'),
            'donor_email' => $request->validated('donor_email'),
            'donor_phone' => $request->validated('donor_phone'),
            'amount' => $request->validated('amount'),
            'currency' => $request->validated('currency'),
            'payment_method' => $request->validated('payment_method'),
            'message' => $request->validated('message'),
            'status' => Donation::STATUS_PENDING,
        ]);

        $session = $this->maxiCash->initiatePayment($donation, [
            'donor_phone' => $request->validated('donor_phone'),
            'donor_email' => $request->validated('donor_email'),
        ]);

        if ($request->expectsJson()) {
            if (! empty($session['redirect_url']) && is_string($session['redirect_url'])) {
                return response()->json([
                    'redirect' => true,
                    'url' => $session['redirect_url'],
                ]);
            }

            if (! empty($session['paid_immediate'])) {
                return response()->json([
                    'redirect' => true,
                    'url' => route('donations.success', ['public_id' => $donation->public_id]),
                ]);
            }

            if (! empty($session['pending'])) {
                $request->session()->flash(
                    'pending_instructions',
                    $session['instructions'] ?? $session['message'] ?? null
                );

                return response()->json([
                    'redirect' => true,
                    'url' => route('donations.pending', ['public_id' => $donation->public_id]),
                ]);
            }

            $message = is_string($session['error'] ?? null)
                ? $session['error']
                : 'Le paiement n’a pas pu démarrer. Vérifie la configuration et les données. Référence : '.$donation->public_id;

            return response()->json([
                'redirect' => false,
                'message' => $message,
            ], 422);
        }

        if (! empty($session['redirect_url']) && is_string($session['redirect_url'])) {
            return redirect()->away($session['redirect_url']);
        }

        if (! empty($session['paid_immediate'])) {
            return redirect()->route('donations.success', ['public_id' => $donation->public_id]);
        }

        if (! empty($session['pending'])) {
            return redirect()
                ->route('donations.pending', ['public_id' => $donation->public_id])
                ->with('pending_instructions', $session['instructions'] ?? $session['message'] ?? null);
        }

        $message = is_string($session['error'] ?? null)
            ? $session['error']
            : 'Le paiement n’a pas pu démarrer. Vérifie la configuration et les données. Référence : '.$donation->public_id;

        return redirect()
            ->route('donations.index')
            ->withInput()
            ->with('status', $message);
    }

    public function success(Request $request): View
    {
        return $this->outcomeView($request, 'donations.success', 'Merci — paiement réussi');
    }

    public function failure(Request $request): View
    {
        return $this->outcomeView($request, 'donations.failure', 'Paiement non abouti');
    }

    public function pending(Request $request): View
    {
        return $this->outcomeView($request, 'donations.pending', 'Paiement en attente');
    }

    public function return(Request $request): View
    {
        $publicId = $request->query('public_id');

        $donation = is_string($publicId)
            ? Donation::query()->where('public_id', $publicId)->first()
            : null;

        return view('donations.return', [
            'donation' => $donation,
        ]);
    }

    private function outcomeView(Request $request, string $view, string $title): View
    {
        $publicId = $request->query('public_id');
        $donation = is_string($publicId)
            ? Donation::query()->where('public_id', $publicId)->first()
            : null;

        return view($view, [
            'donation' => $donation,
            'title' => $title,
            'pendingInstructions' => $request->session()->get('pending_instructions'),
        ]);
    }
}
