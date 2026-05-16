@php
    $methodId = (string) ($method ?? '');
    $methods = collect(config('payment_methods.methods', []))->keyBy('id');
    $methodConfig = $methods->get($methodId);
    $size = $size ?? 'md';
@endphp

@if ($methodConfig)
    <span class="payment-method-icon payment-method-icon--{{ $size }}" title="{{ $methodConfig['label'] }}">
        <img
            src="{{ asset('img/icons/'.$methodConfig['icon']) }}"
            alt="{{ $methodConfig['label'] }}"
            width="64"
            height="32"
            loading="lazy"
        >
    </span>
@else
    <span class="payment-method-icon payment-method-icon--fallback" title="{{ $methodId }}">{{ $methodId }}</span>
@endif
