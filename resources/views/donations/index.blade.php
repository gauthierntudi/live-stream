@extends('layouts.stream')

@section('title', 'Soutenir — '.config('app.name'))

@push('page_loader')
    @include('partials.page-load-overlay')
@endpush

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
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&display=swap" rel="stylesheet">
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
    .soutenir-page--has-bg {
        min-height: 100vh;
        min-height: 100dvh;
    }
    .soutenir-page {
        max-width: 40rem;
        margin: 0 auto;
        padding: 1.25rem 4vw 3rem;
    }
    .soutenir-hero {
        text-align: center;
        margin-bottom: 1.75rem;
        padding: 1.5rem 1rem 0;
    }
    .soutenir-hero__title {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.1rem;
        margin: 0;
        line-height: 1;
    }
    .soutenir-hero__line {
        display: block;
        max-width: 100%;
        line-height: 1;
    }
    .soutenir-hero__line--levites {
        font-family: "Cooper Hewitt", "Montserrat", ui-sans-serif, sans-serif;
        font-weight: 600;
        font-size: clamp(1.35rem, 3.8vw, 1.85rem);
        letter-spacing: 0.08em;
        color: #a8a7a7;
        text-transform: uppercase;
        line-height: 1;
    }
    .soutenir-hero__line--bonsoir {
        font-family: "Bebas Neue", Impact, "Arial Narrow", sans-serif;
        font-weight: 400;
        font-size: clamp(1.85rem, 5.8vw, 2.75rem);
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: #ffffff;
        line-height: 1;
    }
    .soutenir-card {
        padding: 1.75rem 1.5rem 2rem;
        background: var(--bg-elevated);
        border-radius: 14px;
        border: 1px solid rgba(255, 255, 255, 0.08);
        box-shadow: 0 24px 48px rgba(0, 0, 0, 0.35);
    }
    .soutenir-section {
        margin-bottom: 1.5rem;
    }
    .soutenir-section:last-of-type {
        margin-bottom: 0;
    }
    .amount-chips {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-bottom: 0.85rem;
    }
    .amount-chip {
        padding: 0.45rem 0.85rem;
        border-radius: 999px;
        border: 1px solid rgba(255, 255, 255, 0.14);
        background: rgba(255, 255, 255, 0.04);
        color: var(--text);
        font-size: 0.9rem;
        font-weight: 600;
        font-family: inherit;
        cursor: pointer;
        transition: background 0.15s ease, border-color 0.15s ease, transform 0.1s ease;
    }
    .amount-chip:hover {
        background: rgba(255, 255, 255, 0.1);
        border-color: rgba(255, 255, 255, 0.22);
    }
    .amount-chip:active {
        transform: scale(0.97);
    }
    .amount-chip.is-active {
        background: rgba(229, 9, 20, 0.25);
        border-color: rgba(229, 9, 20, 0.55);
        color: #fecaca;
    }
    label {
        display: block;
        font-size: 0.82rem;
        font-weight: 600;
        margin-bottom: 0.4rem;
        color: #e5e7eb;
    }
    .field { margin-bottom: 1.1rem; }
    input, select, textarea {
        width: 100%;
        padding: 0.75rem 0.85rem;
        border-radius: 8px;
        border: 1px solid rgba(255, 255, 255, 0.12);
        background: #0f111a;
        color: var(--text);
        font: inherit;
        font-size: 1rem;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }
    input:focus, select:focus, textarea:focus {
        outline: none;
        border-color: rgba(229, 9, 20, 0.55);
        box-shadow: 0 0 0 3px rgba(229, 9, 20, 0.15);
    }
    textarea { min-height: 100px; resize: vertical; }
    .err {
        color: #fca5a5;
        font-size: 0.85rem;
        margin-top: 0.35rem;
    }
    .soutenir-submit {
        margin-top: 0.5rem;
    }
    .method-grid-wrap {
        margin-top: 8px;
        overflow-x: auto;
        overflow-y: visible;
        margin-inline: -0.35rem;
        padding-inline: 0.35rem;
        padding-bottom: 0.35rem;
        -webkit-overflow-scrolling: touch;
    }
    .method-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 0.5rem;
        min-width: 17.5rem;
    }
    .method-card {
        position: relative;
        box-sizing: border-box;
        display: grid;
        grid-template-columns: 1fr;
        grid-template-rows: 1fr;
        width: 90%;
        justify-self: center;
        aspect-ratio: 1 / 1;
        margin-top: 10px;
        padding: 0.28rem;
        border-radius: 23px;
        border: 2px solid rgba(255, 255, 255, 0.1);
        background: rgba(0, 0, 0, 0.28);
        cursor: pointer;
        transition: border-color 0.15s ease, background 0.15s ease, transform 0.1s ease;
        min-width: 0;
    }
    .method-card:hover {
        border-color: rgba(255, 255, 255, 0.22);
        background: rgba(255, 255, 255, 0.05);
    }
    .method-card:has(input:checked) {
        border-color: rgba(229, 9, 20, 0.8);
        background: rgba(229, 9, 20, 0.14);
    }
    .method-card:focus-within {
        outline: 2px solid rgba(229, 9, 20, 0.55);
        outline-offset: 2px;
    }
    .method-card__input {
        grid-row: 1;
        grid-column: 1;
        position: absolute;
        width: 1px;
        height: 1px;
        padding: 0;
        margin: -1px;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        white-space: nowrap;
        border: 0;
    }
    .method-card__img {
        grid-row: 1;
        grid-column: 1;
        width: 100%;
        height: 100%;
        min-width: 0;
        min-height: 0;
        object-fit: contain;
        object-position: center;
        border-radius: 20px;
        pointer-events: none;
    }
    @media (min-width: 420px) {
        .method-card { padding: 0.32rem; }
    }
    .soutenir-form-global-err {
        margin-bottom: 1rem;
        padding: 0.75rem 0.9rem;
        border-radius: 8px;
        background: rgba(248, 113, 113, 0.12);
        border: 1px solid rgba(248, 113, 113, 0.35);
        color: #fecaca;
        font-size: 0.88rem;
        line-height: 1.45;
    }
    .soutenir-form-global-err[hidden] {
        display: none !important;
    }
    .soutenir-loader {
        position: fixed;
        inset: 0;
        z-index: 100;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1.5rem;
        background: radial-gradient(ellipse 85% 65% at 50% 45%, rgba(0, 0, 0, 0.45) 0%, rgba(0, 0, 0, 0.78) 100%);
        backdrop-filter: blur(6px);
        -webkit-backdrop-filter: blur(6px);
        animation: soutenir-loader-fade-in 0.28s ease-out;
    }
    .soutenir-loader[hidden] {
        display: none !important;
    }
    @keyframes soutenir-loader-fade-in {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    .soutenir-loader__panel {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 1.35rem;
        max-width: 20rem;
        width: 100%;
        padding: 2rem 1.75rem 1.85rem;
        border-radius: 1.1rem;
        background: linear-gradient(165deg, rgba(30, 30, 42, 0.98) 0%, rgba(20, 20, 31, 0.99) 100%);
        border: 1px solid rgba(255, 255, 255, 0.12);
        box-shadow:
            0 0 0 1px rgba(0, 0, 0, 0.4) inset,
            0 0 48px rgba(0, 0, 0, 0.45),
            0 28px 56px rgba(0, 0, 0, 0.65);
        text-align: center;
        animation: soutenir-loader-panel-in 0.4s cubic-bezier(0.22, 1, 0.36, 1) both;
    }
    @keyframes soutenir-loader-panel-in {
        from {
            opacity: 0;
            transform: translateY(1rem) scale(0.94);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }
    .soutenir-loader__rings {
        position: relative;
        width: 3.5rem;
        height: 3.5rem;
        flex-shrink: 0;
    }
    .soutenir-loader__ring {
        position: absolute;
        inset: 0;
        border-radius: 50%;
        border: 3px solid transparent;
        border-top-color: var(--accent);
        border-right-color: rgba(229, 9, 20, 0.35);
        animation: soutenir-loader-spin 1s cubic-bezier(0.5, 0.1, 0.3, 0.9) infinite;
    }
    .soutenir-loader__ring--outer {
        inset: -5px;
        border-width: 2px;
        border-top-color: rgba(255, 255, 255, 0.22);
        border-right-color: transparent;
        animation-duration: 1.45s;
        animation-direction: reverse;
        opacity: 0.85;
    }
    @keyframes soutenir-loader-spin {
        to { transform: rotate(360deg); }
    }
    .soutenir-loader__text {
        display: flex;
        flex-direction: column;
        gap: 0.45rem;
        align-items: center;
    }
    .soutenir-loader__title {
        font-size: 1.05rem;
        font-weight: 700;
        letter-spacing: 0.02em;
        color: #f9fafb;
        line-height: 1.25;
    }
    .soutenir-loader__subtitle {
        font-size: 0.82rem;
        font-weight: 500;
        line-height: 1.45;
        color: #9ca3af;
        max-width: 16rem;
    }
    .soutenir-loader__dots {
        display: inline-flex;
        gap: 0.2rem;
        margin-top: 0.35rem;
        height: 1rem;
        align-items: flex-end;
        font-size: 1.35rem;
        font-weight: 800;
        line-height: 1;
        color: var(--accent);
        letter-spacing: 0.02em;
    }
    .soutenir-loader__dots span {
        display: inline-block;
        animation: soutenir-loader-dot 1.25s ease-in-out infinite;
        opacity: 0.35;
    }
    .soutenir-loader__dots span:nth-child(2) {
        animation-delay: 0.2s;
    }
    .soutenir-loader__dots span:nth-child(3) {
        animation-delay: 0.4s;
    }
    @keyframes soutenir-loader-dot {
        0%, 80%, 100% {
            opacity: 0.25;
            transform: translateY(0);
        }
        40% {
            opacity: 1;
            transform: translateY(-4px);
        }
    }
    @media (prefers-reduced-motion: reduce) {
        .soutenir-loader {
            animation: none;
        }
        .soutenir-loader__panel {
            animation: none;
        }
        .soutenir-loader__ring {
            animation-duration: 1.6s;
        }
        .soutenir-loader__dots span {
            animation: none;
            opacity: 0.7;
        }
    }
</style>
@endpush

@section('content')
    <div class="soutenir-page{{ $successBgUrl ? ' soutenir-page--has-bg' : '' }}">
        <header class="soutenir-hero">
            <h1 class="soutenir-hero__title">
                <span class="soutenir-hero__line soutenir-hero__line--levites">Nuit des Lévites</span>
                <span class="soutenir-hero__line soutenir-hero__line--bonsoir">Bonsoir Saint-Esprit</span>
            </h1>
        </header>

        <div class="soutenir-card">
            @if (! count($paymentMethods))
                <p class="err" style="margin:0;">
                    Aucun moyen de paiement disponible. Vérifie que les fichiers listés dans
                    <code style="color:#fca5a5;">config/payment_methods.php</code> sont bien présents dans
                    <code style="color:#fca5a5;">resources/img/icons/</code>.
                </p>
            @else
                @php
                    $defaultPayment = $paymentMethods[0]['id'];
                @endphp
                <form method="post" action="{{ route('donations.store') }}" id="soutenir-form" autocomplete="off">
                    @csrf
                    <div id="soutenir-form-global-err" class="soutenir-form-global-err" role="alert" hidden></div>

                    <div class="soutenir-section">
                        <div class="method-grid-wrap">
                        <div class="method-grid" role="radiogroup" aria-label="Moyen de paiement">
                            @foreach ($paymentMethods as $method)
                                <label class="method-card" aria-label="{{ $method['label'] }}">
                                    <input
                                        class="method-card__input"
                                        type="radio"
                                        name="payment_method"
                                        value="{{ $method['id'] }}"
                                        @checked(old('payment_method', $defaultPayment) === $method['id'])
                                        required
                                    >
                                    <img
                                        class="method-card__img"
                                        src="{{ asset('img/icons/'.$method['icon']) }}"
                                        alt="{{ $method['label'] }}"
                                        loading="lazy"
                                        width="120"
                                        height="60"
                                    >
                                </label>
                            @endforeach
                        </div>
                        </div>
                        @error('payment_method')<div class="err">{{ $message }}</div>@enderror
                    </div>

                <div class="soutenir-section">
                    <div class="amount-chips" id="amount-chips" role="group" aria-label="Montants rapides">
                        @foreach ([50, 100, 250, 500, 1000, 2500] as $chip)
                            <button type="button" class="amount-chip" data-amount="{{ $chip }}">{{ number_format($chip, 0, ',', ' ') }}</button>
                        @endforeach
                    </div>
                    <div class="field">
                        <label for="amount">Montant (USD)</label>
                        <input type="hidden" name="currency" value="USD">
                        <input id="amount" name="amount" type="number" step="0.01" min="1" value="{{ old('amount', '100') }}" placeholder="100" required inputmode="decimal" autocomplete="off">
                        @error('amount')<div class="err">{{ $message }}</div>@enderror
                        @error('currency')<div class="err">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="soutenir-section">
                    <div class="field">
                        <label for="donor_name">Nom</label>
                        <input id="donor_name" name="donor_name" value="{{ old('donor_name') }}" placeholder="Ex. Marie L." autocomplete="off">
                        @error('donor_name')<div class="err">{{ $message }}</div>@enderror
                    </div>
                    <div class="field">
                        <label for="donor_phone">Téléphone <span style="color:#fca5a5;">*</span></label>
                        <input id="donor_phone" name="donor_phone" value="{{ old('donor_phone') }}" placeholder="243…, +243… ou 0XXXXXXXXX" required autocomplete="off">
                        @error('donor_phone')<div class="err">{{ $message }}</div>@enderror
                    </div>
                    <div class="field">
                        <label for="donor_email" id="donor-email-label">E-mail</label>
                        <input id="donor_email" name="donor_email" type="email" value="{{ old('donor_email') }}" placeholder="Pour le reçu (obligatoire si carte)" autocomplete="off" @if (old('payment_method', $defaultPayment) === 'card') required @endif>
                        @error('donor_email')<div class="err">{{ $message }}</div>@enderror
                    </div>


                    <div class="field">
                        <label for="message">Message</label>
                        <textarea id="message" name="message" rows="3" placeholder="Un mot d’encouragement, une intention…" autocomplete="off">{{ old('message') }}</textarea>
                        @error('message')<div class="err">{{ $message }}</div>@enderror
                    </div>
                </div>

                <button class="btn btn-gold btn--block soutenir-submit" type="submit">
                    <i data-lucide="banknote" aria-hidden="true"></i> Soutenir
                </button>
            </form>
            @endif
        </div>

        <div id="soutenir-loader" class="soutenir-loader" hidden aria-hidden="true">
            <div class="soutenir-loader__panel" role="status" aria-live="polite">
                <div class="soutenir-loader__rings" aria-hidden="true">
                    <span class="soutenir-loader__ring soutenir-loader__ring--outer"></span>
                    <span class="soutenir-loader__ring"></span>
                </div>
                <div class="soutenir-loader__text">
                    <span class="soutenir-loader__title">Préparation du paiement</span>
                    <span class="soutenir-loader__subtitle">Connexion sécurisée en cours, merci de patienter.</span>
                    <span class="soutenir-loader__dots" aria-hidden="true"><span>.</span><span>.</span><span>.</span></span>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
@vite(['resources/js/page-loader.js'])
<script>
(function () {
    var form = document.getElementById('soutenir-form');
    if (!form) return;

    var input = document.getElementById('amount');
    var chips = document.querySelectorAll('.amount-chip');
    if (input && chips.length) {
        function syncActive() {
            var v = parseFloat(String(input.value).replace(',', '.')) || 0;
            chips.forEach(function (btn) {
                var a = Number(btn.getAttribute('data-amount'));
                btn.classList.toggle('is-active', Math.abs(a - v) < 0.009);
            });
        }

        chips.forEach(function (btn) {
            btn.addEventListener('click', function () {
                input.value = btn.getAttribute('data-amount');
                syncActive();
                input.focus();
            });
        });

        input.addEventListener('input', syncActive);
        syncActive();
    }

    var emailInput = document.getElementById('donor_email');
    var emailLabel = document.getElementById('donor-email-label');
    function syncPaymentMethodUi() {
        var checked = form.querySelector('input[name="payment_method"]:checked');
        var method = checked ? checked.value : '';
        var isCard = method === 'card';
        if (emailInput) {
            if (isCard) {
                emailInput.setAttribute('required', 'required');
            } else {
                emailInput.removeAttribute('required');
            }
        }
        if (emailLabel) {
            emailLabel.innerHTML = isCard
                ? 'E-mail <span style="color:#fca5a5;">*</span>'
                : 'E-mail';
        }
    }
    form.querySelectorAll('input[name="payment_method"]').forEach(function (r) {
        r.addEventListener('change', syncPaymentMethodUi);
    });
    syncPaymentMethodUi();

    var loader = document.getElementById('soutenir-loader');
    var globalErr = document.getElementById('soutenir-form-global-err');
    var submitBtn = form.querySelector('button[type="submit"]');

    function clearAjaxErrors() {
        form.querySelectorAll('.err[data-ajax]').forEach(function (el) {
            el.remove();
        });
        if (globalErr) {
            globalErr.textContent = '';
            globalErr.hidden = true;
        }
    }

    function showLoader(visible) {
        if (loader) {
            loader.hidden = !visible;
            loader.setAttribute('aria-hidden', visible ? 'false' : 'true');
        }
        if (submitBtn) submitBtn.disabled = !!visible;
        form.setAttribute('aria-busy', visible ? 'true' : 'false');
    }

    function appendFieldError(field, text) {
        var errEl = document.createElement('div');
        errEl.className = 'err';
        errEl.setAttribute('data-ajax', '1');
        errEl.textContent = text;

        if (field === 'payment_method') {
            var wrap = form.querySelector('.method-grid-wrap');
            if (wrap && wrap.parentNode) {
                wrap.parentNode.appendChild(errEl);
                return;
            }
        }

        var control = form.elements.namedItem(field);
        var anchor = control && control.length && !control.tagName ? control[0] : control;
        if (!anchor) {
            if (globalErr) {
                globalErr.textContent = globalErr.textContent
                    ? globalErr.textContent + ' ' + text
                    : text;
                globalErr.hidden = false;
            }
            return;
        }
        var fieldBox = anchor.closest('.field');
        if (fieldBox) {
            fieldBox.appendChild(errEl);
        } else if (globalErr) {
            globalErr.textContent = globalErr.textContent
                ? globalErr.textContent + ' ' + text
                : text;
            globalErr.hidden = false;
        }
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        clearAjaxErrors();

        var meta = document.querySelector('meta[name="csrf-token"]');
        var headers = {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        };
        if (meta && meta.getAttribute('content')) {
            headers['X-CSRF-TOKEN'] = meta.getAttribute('content');
        }

        showLoader(true);
        fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: headers,
            credentials: 'same-origin',
        })
            .then(function (res) {
                return res.text().then(function (text) {
                    var data = {};
                    try {
                        data = text ? JSON.parse(text) : {};
                    } catch (err) {
                        data = {};
                    }
                    return { res: res, data: data };
                });
            })
            .then(function (_ref) {
                var res = _ref.res;
                var data = _ref.data;

                if (res.ok && data.redirect && data.url) {
                    window.location.href = data.url;
                    return;
                }

                showLoader(false);

                if (res.status === 422 && data.errors) {
                    var keys = Object.keys(data.errors);
                    if (keys.length === 0 && globalErr && data.message) {
                        globalErr.textContent = data.message;
                        globalErr.hidden = false;
                    }
                    keys.forEach(function (field) {
                        var msgs = data.errors[field];
                        if (!Array.isArray(msgs) || !msgs.length) return;
                        appendFieldError(field, msgs.join(' '));
                    });
                    return;
                }

                if (data.message) {
                    if (globalErr) {
                        globalErr.textContent = data.message;
                        globalErr.hidden = false;
                    }
                    return;
                }

                if (globalErr) {
                    globalErr.textContent = res.status === 419
                        ? 'Session expirée. Recharge la page puis réessaie.'
                        : 'Une erreur est survenue. Réessaie dans un instant.';
                    globalErr.hidden = false;
                }
            })
            .catch(function () {
                showLoader(false);
                if (globalErr) {
                    globalErr.textContent = 'Connexion impossible. Vérifie ta connexion.';
                    globalErr.hidden = false;
                }
            });
    });
})();
</script>
@endpush
