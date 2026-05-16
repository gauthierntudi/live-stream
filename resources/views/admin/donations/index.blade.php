@extends('layouts.admin')

@section('title', 'Paiements')

@section('content')
    <section class="stats" aria-label="Statistiques des paiements">
        <x-admin-stat-card icon="receipt" label="Total" :value="number_format($stats['total'])" tone="default" />
        <x-admin-stat-card icon="circle-check" label="Réussis" :value="number_format($stats['paid'])" tone="success" />
        <x-admin-stat-card icon="clock" label="En attente" :value="number_format($stats['pending'])" tone="warning" />
        <x-admin-stat-card icon="circle-x" label="Échoués / annulés" :value="number_format($stats['failed'])" tone="danger" />
        <x-admin-stat-card icon="coins" label="Montant encaissé" :value="number_format($stats['paid_amount'], 2, ',', ' ')" tone="gold" />
    </section>

    <form
        id="donations-filters"
        class="filters-bar"
        method="get"
        action="{{ route('admin.donations.index') }}"
    >
        <label>
            Par page
            <select name="per_page" data-auto-filter aria-label="Nombre de lignes par page">
                @foreach ($perPageOptions as $n)
                    <option value="{{ $n }}" @selected($filters['per_page'] === $n)>{{ $n }}</option>
                @endforeach
            </select>
        </label>
        <label>
            Statut
            <select name="status" data-auto-filter>
                <option value="">Tous</option>
                @foreach ($statuses as $s)
                    <option value="{{ $s }}" @selected($filters['status'] === $s)>
                        @php
                            $labels = [
                                'pending' => 'En attente',
                                'processing' => 'En cours',
                                'paid' => 'Réussi',
                                'failed' => 'Échoué',
                                'cancelled' => 'Annulé',
                            ];
                        @endphp
                        {{ $labels[$s] ?? $s }}
                    </option>
                @endforeach
            </select>
        </label>
        <label>
            Méthode
            <select name="method" data-auto-filter>
                <option value="">Toutes</option>
                @foreach ($methods as $m)
                    <option value="{{ $m }}" @selected($filters['method'] === $m)>{{ $m }}</option>
                @endforeach
            </select>
        </label>
        <label class="filters-bar__field filters-bar__field--grow">
            Recherche
            <span class="filters-bar__field">
                <span class="filters-bar__icon" aria-hidden="true"><i data-lucide="search"></i></span>
                <input
                    type="search"
                    name="q"
                    value="{{ $filters['q'] }}"
                    placeholder="Nom, e-mail, référence…"
                    data-auto-filter-search
                    autocomplete="off"
                >
            </span>
        </label>
        @if ($filters['status'] || $filters['method'] || $filters['q'] || $filters['per_page'] !== $perPageDefault)
            <div class="filters-bar__actions">
                <a class="btn btn-secondary" href="{{ route('admin.donations.index') }}">
                    <i data-lucide="rotate-ccw" aria-hidden="true"></i>
                    Réinitialiser
                </a>
            </div>
        @endif
    </form>

    <div class="table-card">
        <div class="table-card__header">
            <h2 class="table-card__title">Liste des paiements</h2>
            <span class="table-card__meta">
                @if ($donations->total() === 0)
                    Aucun résultat
                @else
                    {{ $donations->total() }} paiement{{ $donations->total() > 1 ? 's' : '' }}
                    @if ($donations->hasPages())
                        · page {{ $donations->currentPage() }} / {{ $donations->lastPage() }}
                    @endif
                @endif
            </span>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Référence</th>
                        <th>Donateur</th>
                        <th>Montant</th>
                        <th>Méthode</th>
                        <th>Statut</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($donations as $donation)
                        <tr>
                            <td>{{ $donation->created_at?->format('d/m/Y H:i') }}</td>
                            <td class="mono">{{ \Illuminate\Support\Str::limit($donation->public_id, 14) }}</td>
                            <td class="data-table__donor">
                                <strong>{{ $donation->donor_name }}</strong>
                                <span>{{ $donation->donor_email }}</span>
                            </td>
                            <td class="data-table__amount">{{ number_format((float) $donation->amount, 2, ',', ' ') }} {{ $donation->currency }}</td>
                            <td class="data-table__method-cell">
                                @include('partials.payment-method-icon', ['method' => $donation->payment_method, 'size' => 'md'])
                            </td>
                            <td>
                                <span class="badge badge--{{ $donation->statusColor() }}">{{ $donation->statusLabel() }}</span>
                            </td>
                            <td>
                                <button
                                    type="button"
                                    class="btn btn-secondary btn-table"
                                    data-donation-modal="{{ route('admin.donations.show', $donation) }}"
                                >Détail</button>
                            </td>
                        </tr>
                    @empty
                        <tr class="data-table__empty">
                            <td colspan="7">Aucun paiement ne correspond à ces filtres.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($donations->total() > 0)
            <div class="table-card__footer table-card__footer--pagination">
                <p class="pagination-summary">
                    Affichage
                    <strong>{{ $donations->firstItem() }}</strong>–<strong>{{ $donations->lastItem() }}</strong>
                    sur <strong>{{ $donations->total() }}</strong>
                </p>
                {{ $donations->onEachSide(1)->links('vendor.pagination.admin') }}
            </div>
        @endif
    </div>
    <dialog id="donation-modal" class="donation-modal" aria-labelledby="donation-modal-title">
        <div class="donation-modal__header">
            <h2 id="donation-modal-title" class="donation-modal__title">Détail du paiement</h2>
            <button type="button" class="donation-modal__close" data-donation-modal-close aria-label="Fermer">
                <i data-lucide="x" aria-hidden="true"></i>
            </button>
        </div>
        <div id="donation-modal-body" class="donation-modal__body"></div>
    </dialog>
@endsection

@push('scripts')
@vite(['resources/js/admin-donation-modal.js'])
<script>
(function () {
    const form = document.getElementById('donations-filters');
    if (!form) return;

    form.querySelectorAll('[data-auto-filter]').forEach((el) => {
        el.addEventListener('change', () => form.requestSubmit());
    });

    const search = form.querySelector('[data-auto-filter-search]');
    if (search) {
        let timer;
        search.addEventListener('input', () => {
            clearTimeout(timer);
            timer = setTimeout(() => form.requestSubmit(), 420);
        });
    }
})();
</script>
@endpush
