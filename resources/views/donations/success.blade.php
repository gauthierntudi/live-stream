@extends('layouts.stream')

@section('title', ($title ?? 'Merci').' — '.config('app.name'))

@php
    $successBgUrl = null;
    $bgConfig = config('app.donation_success_background');
    if (is_string($bgConfig) && trim($bgConfig) !== '') {
        $t = trim($bgConfig);
        $successBgUrl = str_starts_with($t, 'http://') || str_starts_with($t, 'https://')
            ? $t
            : asset(ltrim($t, '/'));
    } else {
        foreach (['webp', 'jpg', 'jpeg', 'png'] as $ext) {
            if (is_file(public_path('img/success-bg.'.$ext))) {
                $successBgUrl = asset('img/success-bg.'.$ext);
                break;
            }
        }
    }
@endphp

@if ($successBgUrl)
    @push('body_start')
        <div
            class="pay-receipt-fixed-bg"
            style='--pay-receipt-bg: url({{ json_encode($successBgUrl) }})'
            aria-hidden="true"
        >
            <div class="pay-receipt-fixed-bg__scrim"></div>
        </div>
    @endpush
@endif

@section('nav_actions')
    <a class="btn btn-secondary" href="{{ route('home') }}"><i data-lucide="home" aria-hidden="true"></i><span class="nav-btn-label">Accueil</span></a>
    <a class="btn btn-primary" href="{{ route('live.show') }}"><i data-lucide="tv-minimal-play" aria-hidden="true"></i><span class="nav-btn-label">Live</span></a>
@endsection

@push('styles')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@600;700&display=swap" rel="stylesheet">
<style>
    .pay-receipt-fixed-bg {
        position: fixed;
        inset: 0;
        z-index: 0;
        pointer-events: none;
        background-color: var(--bg);
        background-image: var(--pay-receipt-bg);
        background-size: cover;
        background-position: center center;
        background-repeat: no-repeat;
        background-attachment: fixed;
    }
    .pay-receipt-fixed-bg__scrim {
        position: absolute;
        inset: 0;
        background: linear-gradient(
            165deg,
            rgba(11, 11, 15, 0.5) 0%,
            rgba(11, 11, 15, 0.78) 42%,
            rgba(11, 11, 15, 0.92) 100%
        );
    }
    body:has(.pay-receipt-fixed-bg) main {
        position: relative;
        z-index: 1;
    }
    .pay-receipt-wrap {
        position: relative;
        isolation: isolate;
        box-sizing: border-box;
        width: 100%;
        min-height: min(88vh, 52rem);
        padding: 2rem 0 3rem;
        background-color: transparent;
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        background-attachment: scroll;
    }
    .pay-receipt-wrap--has-bg {
        min-height: 100vh;
        min-height: 100dvh;
    }
    .pay-receipt {
        position: relative;
        z-index: 1;
        max-width: min(36rem, 100%);
        margin: 0 auto;
        padding: 2.5rem 4vw 3.5rem;
    }
    .pay-receipt__card {
        padding: 2.5rem 2rem 2.25rem;
        border-radius: 1.25rem;
        background: var(--bg-elevated);
        border: 1px solid rgba(255, 255, 255, 0.1);
        box-shadow:
            0 0 0 1px rgba(0, 0, 0, 0.35) inset,
            0 32px 64px rgba(0, 0, 0, 0.45);
    }
    .pay-receipt__hero {
        text-align: center;
        margin-bottom: 2rem;
    }
    .pay-receipt__mark {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 4.75rem;
        height: 4.75rem;
        margin: 0 auto 1.25rem;
        border-radius: 50%;
        background: #22ce06;
        border: none;
        color: #ffffff;
    }
    .pay-receipt__mark svg {
        width: 2.35rem;
        height: 2.35rem;
        stroke-width: 2.75px;
    }
    .pay-receipt__hero h1 {
        font-family: "Google Sans", ui-sans-serif, system-ui, sans-serif;
        font-size: clamp(1.65rem, 4.5vw, 2rem);
        font-weight: 700;
        letter-spacing: 0;
        margin: 0 0 0.65rem;
        line-height: 1.15;
        color: #fafafa;
    }
    .pay-receipt__lead {
        margin: 0 auto;
        max-width: 28rem;
        font-size: 0.98rem;
        line-height: 1.55;
        color: var(--muted);
    }
    .pay-receipt__panel {
        padding: 1.35rem 1.25rem 1.25rem;
        border-radius: 0.9rem;
        background: rgba(0, 0, 0, 0.35);
        border: 1px solid rgba(255, 255, 255, 0.07);
    }
    .pay-receipt__rows {
        margin: 0;
        display: flex;
        flex-direction: column;
        gap: 0.95rem;
    }
    .pay-receipt__row {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 1rem 1.25rem;
        align-items: baseline;
        font-size: 0.9rem;
    }
    .pay-receipt__row dt {
        margin: 0;
        color: #9ca3af;
        font-weight: 500;
    }
    .pay-receipt__row dd {
        margin: 0;
        text-align: right;
        font-weight: 600;
        color: #f3f4f6;
    }
    .pay-receipt__row dd.pay-receipt__amount {
        font-size: 1.12rem;
        font-weight: 700;
        font-variant-numeric: tabular-nums;
        color: #fef08a;
    }
    .pay-receipt__pill {
        display: inline-block;
        max-width: 100%;
        padding: 0.35rem 0.65rem;
        border-radius: 999px;
        font-family: ui-monospace, monospace;
        font-size: 0.72rem;
        font-weight: 600;
        color: #e5e7eb;
        background: rgba(255, 255, 255, 0.07);
        border: 1px solid rgba(255, 255, 255, 0.12);
        word-break: break-all;
        text-align: right;
    }
    .pay-receipt__notice {
        display: flex;
        align-items: flex-start;
        gap: 0.65rem;
        margin-top: 1.25rem;
        padding: 0.9rem 1rem;
        border-radius: 0.75rem;
        background: rgba(56, 189, 248, 0.1);
        border: 1px solid rgba(56, 189, 248, 0.28);
        color: #7dd3fc;
        font-size: 0.88rem;
        line-height: 1.45;
    }
    .pay-receipt__notice svg {
        width: 1.1rem;
        height: 1.1rem;
        flex-shrink: 0;
        margin-top: 0.12rem;
    }
    .pay-receipt__notice strong {
        color: #e0f2fe;
        font-weight: 600;
    }
    .pay-receipt__actions {
        margin-top: 1.85rem;
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }
    .pay-receipt__actions .btn--block {
        width: 100%;
    }
    .pay-receipt__foot {
        margin: 1.5rem 0 0;
        text-align: center;
        font-size: 0.8rem;
        line-height: 1.5;
        color: #6b7280;
    }
    .pay-receipt__foot a {
        color: #9ca3af;
        text-decoration: underline;
        text-underline-offset: 0.15em;
    }
    .pay-receipt__foot a:hover {
        color: #d1d5db;
    }
</style>
@endpush

@section('content')
    <div class="pay-receipt-wrap{{ $successBgUrl ? ' pay-receipt-wrap--has-bg' : '' }}">
        <div class="pay-receipt">
            <div class="pay-receipt__card">
            <header class="pay-receipt__hero">
                <div class="pay-receipt__mark" aria-hidden="true">
                    <i data-lucide="check"></i>
                </div>
                <h1>Paiement réussi !</h1>
                <p class="pay-receipt__lead">Ta transaction est enregistrée. Merci pour ton soutien.</p>
            </header>

            @if ($donation)
                @php
                    $pmRow = collect(config('payment_methods.methods'))->firstWhere('id', $donation->payment_method);
                    $pmLabel = is_array($pmRow) && isset($pmRow['label']) ? $pmRow['label'] : $donation->payment_method;
                    $when = $donation->created_at?->timezone(config('app.timezone'))->format('d/m/Y à H:i');
                @endphp
                <div class="pay-receipt__panel">
                    <dl class="pay-receipt__rows">
                        <div class="pay-receipt__row">
                            <dt>Montant</dt>
                            <dd class="pay-receipt__amount">{{ number_format((float) $donation->amount, 2, ',', ' ') }} {{ $donation->currency }}</dd>
                        </div>
                        <div class="pay-receipt__row">
                            <dt>Référence</dt>
                            <dd><span class="pay-receipt__pill">{{ $donation->public_id }}</span></dd>
                        </div>
                        @if ($donation->payment_method)
                            <div class="pay-receipt__row">
                                <dt>Moyen de paiement</dt>
                                <dd>{{ $pmLabel }}</dd>
                            </div>
                        @endif
                        @if ($when)
                            <div class="pay-receipt__row">
                                <dt>Date</dt>
                                <dd>{{ $when }}</dd>
                            </div>
                        @endif
                        <div class="pay-receipt__row">
                            <dt>Événement</dt>
                            <dd>{{ config('app.name') }}</dd>
                        </div>
                    </dl>
                </div>

                @if ($donation->donor_email)
                    <div class="pay-receipt__notice" role="status">
                        <i data-lucide="mail" aria-hidden="true"></i>
                        <span>Un récapitulatif peut t’être envoyé à <strong>{{ $donation->donor_email }}</strong>.</span>
                    </div>
                @endif
            @endif

            <div class="pay-receipt__actions">
                <a class="btn btn-primary btn--block" href="{{ route('live.show') }}">
                    <i data-lucide="circle-play" aria-hidden="true"></i>
                    Retour au live
                </a>
                <a class="btn btn-secondary btn--block" href="{{ route('donations.index') }}">
                    <i data-lucide="arrow-left" aria-hidden="true"></i>
                    Nouveau don
                </a>
            </div>

            <p class="pay-receipt__foot">
                Une question ? <a href="{{ route('home') }}">Retour à l’accueil</a>
            </p>
            </div>
        </div>
    </div>
@endsection
