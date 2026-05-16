@php
    $methodLabels = [
        'card' => 'Carte bancaire',
        'mpesa' => 'M-Pesa',
        'airtel' => 'Airtel Money',
        'orange' => 'Orange Money',
    ];
    $methodLabel = $methodLabels[$donation->payment_method] ?? $donation->payment_method;
@endphp

<div class="donation-modal__hero">
    <div class="donation-modal__hero-main">
        <span class="badge badge--{{ $donation->statusColor() }}">{{ $donation->statusLabel() }}</span>
        <p class="donation-modal__amount">
            {{ number_format((float) $donation->amount, 2, ',', ' ') }}
            <span>{{ $donation->currency }}</span>
        </p>
        <p class="donation-modal__meta">{{ $donation->created_at?->format('d/m/Y à H:i') }}</p>
    </div>
    @include('partials.payment-method-icon', ['method' => $donation->payment_method, 'size' => 'lg'])
</div>

<dl class="donation-modal__grid">
    <div class="donation-modal__item">
        <dt><i data-lucide="hash" aria-hidden="true"></i> Référence</dt>
        <dd class="mono">{{ $donation->public_id }}</dd>
    </div>
    <div class="donation-modal__item">
        <dt><i data-lucide="user" aria-hidden="true"></i> Donateur</dt>
        <dd>{{ $donation->donor_name }}</dd>
    </div>
    <div class="donation-modal__item">
        <dt><i data-lucide="mail" aria-hidden="true"></i> E-mail</dt>
        <dd><a href="mailto:{{ $donation->donor_email }}">{{ $donation->donor_email }}</a></dd>
    </div>
    <div class="donation-modal__item">
        <dt><i data-lucide="phone" aria-hidden="true"></i> Téléphone</dt>
        <dd>{{ $donation->donor_phone ?: '—' }}</dd>
    </div>
    <div class="donation-modal__item donation-modal__item--wide">
        <dt><i data-lucide="message-square" aria-hidden="true"></i> Message</dt>
        <dd>{{ $donation->message ?: '—' }}</dd>
    </div>
    <div class="donation-modal__item">
        <dt><i data-lucide="receipt" aria-hidden="true"></i> Réf. MaxiCash</dt>
        <dd class="mono">{{ $donation->maxicash_reference ?: '—' }}</dd>
    </div>
    <div class="donation-modal__item">
        <dt><i data-lucide="credit-card" aria-hidden="true"></i> Transaction</dt>
        <dd class="mono">{{ $donation->maxicash_transaction_id ?: '—' }}</dd>
    </div>
</dl>

@if ($donation->provider_payload)
    <details class="donation-modal__payload">
        <summary>Payload fournisseur (MaxiCash)</summary>
        <pre class="payload">{{ json_encode($donation->provider_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
    </details>
@endif
