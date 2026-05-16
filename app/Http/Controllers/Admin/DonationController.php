<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Donation;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class DonationController extends Controller
{
    /** @var list<int> */
    private const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    private const PER_PAGE_DEFAULT = 10;

    public function index(Request $request): View
    {
        $status = $request->string('status')->toString();
        $method = $request->string('method')->toString();
        $search = trim($request->string('q')->toString());

        $requestedPerPage = $request->integer('per_page');
        $perPage = in_array($requestedPerPage, self::PER_PAGE_OPTIONS, true)
            ? $requestedPerPage
            : self::PER_PAGE_DEFAULT;

        $query = Donation::query()->latest();

        if ($status !== '' && in_array($status, Donation::STATUSES, true)) {
            $query->where('status', $status);
        }

        if ($method !== '') {
            $query->where('payment_method', $method);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('public_id', 'like', "%{$search}%")
                    ->orWhere('donor_name', 'like', "%{$search}%")
                    ->orWhere('donor_email', 'like', "%{$search}%")
                    ->orWhere('donor_phone', 'like', "%{$search}%")
                    ->orWhere('maxicash_reference', 'like', "%{$search}%")
                    ->orWhere('maxicash_transaction_id', 'like', "%{$search}%");
            });
        }

        $donations = $query->paginate($perPage)->withQueryString();

        $stats = [
            'total' => Donation::query()->count(),
            'paid' => Donation::query()->where('status', Donation::STATUS_PAID)->count(),
            'pending' => Donation::query()->whereIn('status', [
                Donation::STATUS_PENDING,
                Donation::STATUS_PROCESSING,
            ])->count(),
            'failed' => Donation::query()->whereIn('status', [
                Donation::STATUS_FAILED,
                Donation::STATUS_CANCELLED,
            ])->count(),
            'paid_amount' => (float) Donation::query()
                ->where('status', Donation::STATUS_PAID)
                ->sum('amount'),
        ];

        return view('admin.donations.index', [
            'donations' => $donations,
            'stats' => $stats,
            'filters' => [
                'status' => $status,
                'method' => $method,
                'q' => $search,
                'per_page' => $perPage,
            ],
            'perPageDefault' => self::PER_PAGE_DEFAULT,
            'perPageOptions' => self::PER_PAGE_OPTIONS,
            'statuses' => Donation::STATUSES,
            'methods' => Donation::query()
                ->select('payment_method')
                ->distinct()
                ->orderBy('payment_method')
                ->pluck('payment_method')
                ->filter()
                ->values(),
        ]);
    }

    public function show(Request $request, Donation $donation): View|Response
    {
        if ($request->ajax() || $request->boolean('modal')) {
            return response()->view('admin.donations.partials.modal-content', [
                'donation' => $donation,
            ]);
        }

        return view('admin.donations.show', [
            'donation' => $donation,
        ]);
    }
}
