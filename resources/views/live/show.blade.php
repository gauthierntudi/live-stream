@extends('layouts.stream')

@section('title', 'Live — '.config('app.name'))

@section('nav_actions')
    <a class="btn btn-secondary" href="{{ route('home') }}"><i data-lucide="home" aria-hidden="true"></i><span class="nav-btn-label">Accueil</span></a>
    <a class="btn btn-gold" href="{{ route('donations.index') }}"><i data-lucide="heart" aria-hidden="true"></i><span class="nav-btn-label">Soutenir</span></a>
@endsection

@push('styles')
<style>
    body:has(.live-page) main {
        padding-bottom: 0;
        position: relative;
        z-index: 1;
    }
    .live-page {
        width: 100%;
        max-width: 100%;
        margin: 0;
        padding: 0;
        min-height: 100vh;
        min-height: 100dvh;
        min-height: 100lvh;
    }
    .live-page .player-wrap {
        position: fixed;
        top: 0;
        right: 0;
        bottom: 0;
        left: 0;
        z-index: 0;
        padding: 0;
        max-width: none;
        margin: 0;
        width: 100vw;
        height: 100vh;
        height: 100dvh;
        height: 100lvh;
        pointer-events: none;
    }
    .live-page .player-wrap::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 7rem;
        z-index: 10;
        pointer-events: none;
        background: linear-gradient(180deg, rgba(0, 0, 0, 0.78) 0%, transparent 100%);
    }
    .live-page .player-wrap .player {
        pointer-events: auto;
    }
    .live-page .player {
        position: relative;
        width: 100%;
        height: 100%;
        min-height: 100%;
        aspect-ratio: auto;
        background: #0a0a0f;
        border-radius: 0;
        overflow: hidden;
        box-shadow: none;
        isolation: isolate;
    }
    .player--has-wait-bg::before {
        content: '';
        position: absolute;
        inset: 0;
        z-index: 0;
        background-image: var(--live-wait-bg);
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
    }
    .player--has-wait-bg::after {
        content: '';
        position: absolute;
        inset: 0;
        z-index: 1;
        background: linear-gradient(
            165deg,
            rgba(11, 11, 15, 0.45) 0%,
            rgba(11, 11, 15, 0.72) 45%,
            rgba(11, 11, 15, 0.88) 100%
        );
    }
    .player--has-wait-bg .player__overlay {
        z-index: 2;
        background: transparent;
    }
    /* IVS (Video.js) : remplissage cinéma plein écran */
    .live-page .player .live-player-netflix,
    .live-page .player .live-player-netflix .video-js {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
    }
    .live-page .player .live-player-netflix .video-js video {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        max-width: none;
        max-height: none;
        min-width: 100%;
        min-height: 100%;
        transform: none;
        object-fit: cover;
        border: 0;
        background: #000;
    }
    /* Portrait / mobile : voir l’image entière (bandes noires) plutôt que recadrage type « cover » */
    @media (max-width: 896px), (max-height: 520px) {
        .live-page .player .live-player-netflix .video-js video {
            object-fit: contain;
            min-width: 0;
            min-height: 0;
        }
    }
    /*
     * Iframe (Cloudflare / YouTube) : cadrage 16:9 « contain » dans la fenêtre (évite le zoom excessif en portrait).
     */
    .live-page .player iframe {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: min(100vw, calc(100vh * 16 / 9));
        height: min(100vh, calc(100vw * 9 / 16));
        max-width: 100vw;
        max-height: 100vh;
        border: 0;
        background: #000;
    }
    @supports (height: 100dvh) {
        .live-page .player iframe {
            width: min(100vw, calc(100dvh * 16 / 9));
            height: min(100dvh, calc(100vw * 9 / 16));
            max-height: 100dvh;
        }
    }
    .player__overlay {
        position: absolute;
        inset: 0;
        z-index: 4;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        padding: 2rem 1.5rem;
        gap: 1rem;
        background: radial-gradient(ellipse 80% 60% at 50% 40%, rgba(229, 9, 20, 0.12) 0%, transparent 55%),
            linear-gradient(180deg, rgba(18, 18, 26, 0.55) 0%, rgba(10, 10, 15, 0.75) 100%);
        color: var(--muted);
    }
    .player__overlay--waiting .player__pulse {
        width: 4.5rem;
        height: 4.5rem;
        border-radius: 50%;
        border: 2px solid rgba(229, 9, 20, 0.35);
        position: relative;
        flex-shrink: 0;
    }
    .player__overlay--waiting .player__pulse::before {
        content: '';
        position: absolute;
        inset: 0.65rem;
        border-radius: 50%;
        background: rgba(229, 9, 20, 0.85);
        animation: live-pulse 1.8s ease-in-out infinite;
    }
    @keyframes live-pulse {
        0%, 100% { transform: scale(0.85); opacity: 0.65; }
        50% { transform: scale(1); opacity: 1; }
    }
    .player__title {
        margin: 0;
        font-size: clamp(1.25rem, 3vw, 1.65rem);
        font-weight: 600;
        color: #f3f4f6;
        letter-spacing: 0.02em;
    }
    .player__subtitle {
        margin: 0;
        max-width: 28rem;
        font-size: 0.95rem;
        line-height: 1.55;
        color: #9ca3af;
    }
    .player__hint {
        margin: 0;
        font-size: 0.8rem;
        color: #6b7280;
    }
    @media (prefers-reduced-motion: reduce) {
        .player__overlay--waiting .player__pulse::before {
            animation: none;
            opacity: 0.85;
        }
    }
</style>
@endpush

@section('content')
    @php
        $playerWaitBg = ! empty($waitingBgUrl) && ($showWaiting ?? false);
    @endphp

    <div class="live-page">
    <div class="player-wrap">
        <div
            class="player{{ $playerWaitBg ? ' player--has-wait-bg' : '' }}"
            @if ($playerWaitBg)
                style="--live-wait-bg: url({{ json_encode($waitingBgUrl) }})"
            @endif
            @if ($hasPlayerConfig ?? false)
                data-live-poll
                data-live-status-url="{{ route('live.status') }}"
                data-live-poll-interval="8000"
                data-live-content-hash="{{ $initialPlayerInnerHash }}"
            @endif
        >
            @include('live.partials.player-inner')
        </div>
    </div>
    </div>
@endsection

@if (($showPlayer ?? false) && (($playbackMode ?? 'iframe') === 'hls'))
    @push('scripts')
        @vite('resources/js/live-player.js')
    @endpush
@endif

@if ($hasPlayerConfig ?? false)
    @push('scripts')
        @vite('resources/js/live-status-poll-boot.js')
    @endpush
@endif
