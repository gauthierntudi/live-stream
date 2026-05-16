@extends('layouts.admin')

@section('title', 'Live')

@push('styles')
<style>
    .live-admin-page {
        max-width: 1200px;
        margin: 0 auto;
    }
    .live-admin-hero {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1.25rem;
        margin-bottom: 1.75rem;
        padding-bottom: 1.25rem;
        border-bottom: 1px solid var(--border);
    }
    .live-admin-hero__title {
        margin: 0;
        font-size: clamp(1.35rem, 2.5vw, 1.75rem);
        font-weight: 700;
        letter-spacing: -0.02em;
        background: linear-gradient(120deg, #fff 0%, rgba(243, 244, 246, 0.82) 55%, rgba(229, 9, 20, 0.95) 140%);
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
    }
    .live-admin-hero__subtitle {
        margin: 0.45rem 0 0;
        font-size: 0.92rem;
        color: var(--muted);
        line-height: 1.55;
        max-width: 36rem;
    }
    .live-admin-hero__badge {
        flex-shrink: 0;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.35rem 0.75rem;
        border-radius: 999px;
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        border: 1px solid rgba(229, 9, 20, 0.35);
        background: rgba(229, 9, 20, 0.12);
        color: #fecaca;
    }
    .live-admin-shell {
        position: relative;
    }
    #live-admin-root {
        transition: opacity 0.2s ease;
    }
    #live-admin-root.is-live-admin-busy {
        opacity: 0.55;
        pointer-events: none;
    }
    .live-admin-loader {
        position: fixed;
        inset: 0;
        z-index: 9998;
        display: grid;
        place-items: center;
        background: rgba(5, 5, 8, 0.55);
        backdrop-filter: blur(6px);
        -webkit-backdrop-filter: blur(6px);
    }
    .live-admin-loader[hidden] {
        display: none !important;
    }
    .live-admin-loader__card {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1rem 1.35rem;
        border-radius: 14px;
        border: 1px solid var(--border);
        background: linear-gradient(155deg, rgba(31, 41, 55, 0.95) 0%, rgba(15, 15, 22, 0.98) 100%);
        box-shadow: 0 24px 80px rgba(0, 0, 0, 0.45);
        font-size: 0.9rem;
        font-weight: 600;
        color: var(--text);
    }
    .live-admin-loader__spinner {
        width: 1.65rem;
        height: 1.65rem;
        border-radius: 50%;
        border: 2px solid rgba(255, 255, 255, 0.12);
        border-top-color: var(--accent);
        animation: live-admin-spin 0.75s linear infinite;
    }
    @keyframes live-admin-spin {
        to { transform: rotate(360deg); }
    }
    .live-admin-toast {
        position: fixed;
        bottom: 1.35rem;
        right: 1.35rem;
        z-index: 9999;
        max-width: min(22rem, calc(100vw - 2rem));
        padding: 0.9rem 1.15rem;
        border-radius: 12px;
        font-size: 0.88rem;
        font-weight: 500;
        line-height: 1.45;
        box-shadow: 0 16px 48px rgba(0, 0, 0, 0.35);
        border: 1px solid var(--border);
        animation: live-admin-toast-in 0.35s ease;
    }
    .live-admin-toast[hidden] {
        display: none !important;
    }
    .live-admin-toast[data-variant="success"] {
        background: rgba(22, 101, 52, 0.95);
        color: #ecfdf5;
        border-color: rgba(74, 222, 128, 0.35);
    }
    .live-admin-toast[data-variant="error"] {
        background: rgba(127, 29, 29, 0.96);
        color: #fef2f2;
        border-color: rgba(252, 165, 165, 0.4);
    }
    @keyframes live-admin-toast-in {
        from {
            opacity: 0;
            transform: translateY(12px) scale(0.98);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }
    .live-admin-dashboard {
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
    }
    .live-admin-card {
        border-radius: 14px;
        border: 1px solid var(--border);
        background: linear-gradient(165deg, rgba(255, 255, 255, 0.055) 0%, rgba(255, 255, 255, 0.015) 100%);
        overflow: hidden;
        box-shadow: 0 14px 42px rgba(0, 0, 0, 0.18);
    }
    .live-admin-card--accent {
        border-color: rgba(229, 9, 20, 0.22);
        box-shadow: 0 14px 42px rgba(229, 9, 20, 0.08), 0 14px 42px rgba(0, 0, 0, 0.18);
    }
    .live-admin-card--preview .live-admin-card__body--flush {
        padding: 0;
    }
    .live-admin-preview-head {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        padding-bottom: 1rem;
    }
    .live-admin-preview-head__text {
        flex: 1 1 16rem;
        min-width: 0;
    }
    .live-admin-preview-hint {
        margin: 0.65rem 0 0;
        font-size: 0.78rem;
        color: var(--muted);
        line-height: 1.45;
    }
    .live-admin-preview-refresh {
        flex-shrink: 0;
    }
    .live-admin-card--muted {
        padding: 1.15rem 1.25rem;
        background: rgba(11, 11, 15, 0.35);
    }
    .live-admin-card__head {
        padding: 1.15rem 1.25rem 0;
    }
    .live-admin-card__title-row {
        display: flex;
        gap: 1rem;
        align-items: flex-start;
    }
    .live-admin-card__icon {
        flex-shrink: 0;
        width: 2.5rem;
        height: 2.5rem;
        border-radius: 11px;
        display: grid;
        place-items: center;
        background: rgba(229, 9, 20, 0.14);
        color: #fca5a5;
    }
    .live-admin-card__title {
        margin: 0;
        font-size: 1.05rem;
        font-weight: 700;
    }
    .live-admin-card__desc {
        margin: 0.45rem 0 0;
        font-size: 0.875rem;
        color: var(--muted);
        line-height: 1.55;
    }
    .live-admin-card__body {
        padding: 1.1rem 1.25rem 1.25rem;
    }
    .live-admin-code {
        font-family: ui-monospace, monospace;
        font-size: 0.82em;
        padding: 0.1rem 0.35rem;
        border-radius: 6px;
        background: rgba(0, 0, 0, 0.35);
        border: 1px solid rgba(255, 255, 255, 0.06);
    }
    .live-admin-pill {
        display: inline-flex;
        align-items: center;
        padding: 0.15rem 0.55rem;
        border-radius: 999px;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        vertical-align: middle;
        margin: 0 0.2rem;
    }
    .live-admin-pill--warn {
        background: rgba(245, 158, 11, 0.18);
        color: #fcd34d;
        border: 1px solid rgba(245, 158, 11, 0.35);
    }
    .live-admin-pill--accent {
        background: rgba(229, 9, 20, 0.16);
        color: #fecaca;
        border: 1px solid rgba(229, 9, 20, 0.35);
    }
    .live-admin-pill--muted {
        background: rgba(156, 163, 175, 0.12);
        color: #d1d5db;
        border: 1px solid rgba(255, 255, 255, 0.08);
    }
    .live-admin-provider-row {
        display: flex;
        flex-wrap: wrap;
        gap: 0.85rem;
        align-items: flex-end;
    }
    .live-admin-inline-form {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
        align-items: flex-end;
    }
    .live-admin-field {
        display: flex;
        flex-direction: column;
        gap: 0.35rem;
    }
    .live-admin-field__label {
        font-size: 0.68rem;
        font-weight: 700;
        color: var(--muted);
        text-transform: uppercase;
        letter-spacing: 0.07em;
    }
    .live-admin-select {
        min-width: 14rem;
        padding: 0.55rem 0.75rem;
        border-radius: 10px;
        border: 1px solid rgba(255, 255, 255, 0.12);
        background: rgba(11, 11, 15, 0.72);
        color: var(--text);
        font-family: inherit;
        font-size: 0.875rem;
        cursor: pointer;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }
    .live-admin-select:focus {
        outline: none;
        border-color: rgba(229, 9, 20, 0.45);
        box-shadow: 0 0 0 3px rgba(229, 9, 20, 0.12);
    }
    input.live-admin-select {
        cursor: text;
        min-width: min(100%, 28rem);
        width: 100%;
        max-width: 36rem;
    }
    .live-admin-summary {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        align-items: center;
    }
    .live-admin-toolbar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 0.85rem;
        padding: 1rem 1.15rem;
        border-radius: 14px;
        border: 1px solid var(--border);
        background: rgba(11, 11, 15, 0.4);
    }
    .live-admin-toolbar__cluster {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        align-items: center;
    }
    .live-admin-toolbar__form {
        margin: 0;
    }
    .live-admin-toolbar__link {
        flex-shrink: 0;
    }
    .btn--compact {
        padding: 0.48rem 0.85rem;
        font-size: 0.8125rem;
    }
    .live-admin-btn-icon {
        opacity: 0.85;
        font-size: 0.75rem;
        margin-right: 0.15rem;
    }
    .btn.is-loading {
        position: relative;
        pointer-events: none;
        color: transparent !important;
    }
    .btn.is-loading::after {
        content: '';
        position: absolute;
        width: 1.1rem;
        height: 1.1rem;
        border-radius: 50%;
        border: 2px solid rgba(255, 255, 255, 0.2);
        border-top-color: rgba(255, 255, 255, 0.95);
        animation: live-admin-spin 0.7s linear infinite;
    }
    .btn-primary.is-loading::after {
        border-top-color: #fff;
    }
    .btn-gold.is-loading::after {
        border-top-color: #111827;
    }
    .live-admin-alert {
        padding: 1rem 1.15rem;
        border-radius: 12px;
        font-size: 0.9rem;
        line-height: 1.5;
    }
    .live-admin-alert--error {
        background: rgba(239, 68, 68, 0.12);
        border: 1px solid rgba(239, 68, 68, 0.35);
        color: #fecaca;
    }
    .live-admin-stats {
        margin-bottom: 0 !important;
    }
    .live-admin-ingest {
        display: flex;
        flex-direction: column;
        gap: 1.15rem;
    }
    .live-admin-ingest__label {
        display: block;
        font-size: 0.68rem;
        font-weight: 700;
        color: var(--muted);
        text-transform: uppercase;
        letter-spacing: 0.07em;
        margin-bottom: 0.4rem;
    }
    .live-admin-code-block {
        font-family: ui-monospace, monospace;
        font-size: 0.82rem;
        word-break: break-all;
        line-height: 1.45;
    }
    .live-admin-playback-url {
        margin: 0.5rem 0 0;
        font-size: 0.82rem;
        word-break: break-all;
        color: var(--muted);
    }
    .admin-live-preview-shell {
        max-width: 100%;
        margin: 0 auto;
        background: #0a0a0f;
        border-radius: 0;
        overflow: hidden;
        border-top: 1px solid rgba(255, 255, 255, 0.06);
    }
    .admin-live-preview-player {
        position: relative;
        width: 100%;
        aspect-ratio: 16 / 9;
        background: #000;
        isolation: isolate;
    }
    .admin-live-preview-player .player__overlay {
        position: absolute;
        inset: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        padding: 1.5rem 1rem;
        gap: 0.75rem;
        background: linear-gradient(180deg, rgba(18, 18, 26, 0.72) 0%, rgba(10, 10, 15, 0.88) 100%);
        color: var(--muted);
    }
    .admin-live-preview-player .player__pulse {
        width: 3rem;
        height: 3rem;
        border-radius: 50%;
        border: 2px solid rgba(229, 9, 20, 0.35);
        position: relative;
    }
    .admin-live-preview-player .player__pulse::before {
        content: '';
        position: absolute;
        inset: 0.45rem;
        border-radius: 50%;
        background: rgba(229, 9, 20, 0.85);
        animation: admin-live-pulse 1.8s ease-in-out infinite;
    }
    .admin-live-preview-player .player__title {
        margin: 0;
        font-size: 1rem;
        font-weight: 600;
        color: #f3f4f6;
    }
    .admin-live-preview-player .player__subtitle {
        margin: 0;
        font-size: 0.85rem;
        line-height: 1.5;
        max-width: 22rem;
        color: #9ca3af;
    }
    .admin-live-preview-player iframe {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        border: 0;
        background: #000;
    }
    .admin-live-preview-player .live-player-netflix,
    .admin-live-preview-player .live-player-netflix .video-js {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
    }
    .admin-live-preview-player .live-player-netflix .video-js video {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        border: 0;
        background: #000;
        object-fit: contain;
    }
    @keyframes admin-live-pulse {
        0%, 100% { transform: scale(0.85); opacity: 0.65; }
        50% { transform: scale(1); opacity: 1; }
    }
    @media (prefers-reduced-motion: reduce) {
        .admin-live-preview-player .player__pulse::before,
        .live-admin-loader__spinner,
        .btn.is-loading::after {
            animation: none !important;
        }
        .live-admin-toast {
            animation: none !important;
        }
    }
</style>
@endpush

@section('content')
    <div class="live-admin-page">
        <header class="live-admin-hero">
            <div>
                <h1 class="live-admin-hero__title">Console Live</h1>
                <p class="live-admin-hero__subtitle">
                    Préparez la diffusion OBS, contrôlez la visibilité publique et vérifiez le flux avant l’ouverture au grand public.
                </p>
            </div>
            <span class="live-admin-hero__badge" title="Prévisualisation réservée à l’admin">Admin · prévisualisation sécurisée</span>
        </header>

        <div class="live-admin-shell">
            <div class="live-admin-loader" id="live-admin-loader" hidden>
                <div class="live-admin-loader__card">
                    <span class="live-admin-loader__spinner" aria-hidden="true"></span>
                    <span>Mise à jour en cours…</span>
                </div>
            </div>
            <div class="live-admin-toast" id="live-admin-toast" role="status" aria-live="polite" data-variant="success" hidden></div>
            <div id="live-admin-root">
                @include('admin.live.partials.dashboard-inner')
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/admin-live-dashboard.js')
@endpush
