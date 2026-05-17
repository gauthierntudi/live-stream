@extends('layouts.stream')

@section('title', 'Accueil — '.config('app.name'))

@section('nav_actions')
    <a class="btn btn-secondary" href="{{ route('live.show') }}"><i data-lucide="tv-minimal-play" aria-hidden="true"></i><span class="nav-btn-label">Live</span></a>
    <a class="btn btn-gold" href="{{ route('donations.index') }}"><i data-lucide="heart" aria-hidden="true"></i><span class="nav-btn-label">Soutenir</span></a>
@endsection

@push('page_loader')
    @include('partials.page-load-overlay')
@endpush

@push('styles')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&display=swap" rel="stylesheet">
<style>
    body:has(.hero--full) {
        overflow-x: clip;
    }
    body:has(.hero--full) main {
        padding-bottom: 0;
    }
    .hero.hero--full {
        --nav-area: 7rem;
        position: relative;
        z-index: 1;
        min-height: 100vh;
        min-height: 100svh;
        min-height: 100dvh;
        min-height: 100lvh;
        margin-top: calc(-1 * var(--nav-area));
        padding: calc(var(--nav-area) + 1.5rem) 4vw 3rem;
        display: flex;
        flex-direction: column;
        justify-content: flex-end;
        box-sizing: border-box;
    }
    .hero.hero--full .hero__media--carousel {
        position: fixed;
        top: 0;
        right: 0;
        bottom: 0;
        left: 0;
        width: 100vw;
        min-height: 100vh;
        min-height: 100svh;
        min-height: 100dvh;
        min-height: 100lvh;
        height: 100lvh;
        z-index: 0;
        pointer-events: none;
    }
    .hero.hero--full .hero__scrim {
        position: fixed;
        top: 0;
        right: 0;
        bottom: 0;
        left: 0;
        width: 100vw;
        min-height: 100vh;
        min-height: 100svh;
        min-height: 100dvh;
        min-height: 100lvh;
        height: 100lvh;
        z-index: 1;
        pointer-events: none;
    }
    .hero__media--carousel {
        z-index: 0;
    }
    .hero__scrim {
        z-index: 1;
        pointer-events: none;
        background:
            linear-gradient(
                180deg,
                rgba(11, 11, 15, 0.35) 0%,
                rgba(11, 11, 15, 0.55) 38%,
                rgba(11, 11, 15, 0.88) 78%,
                rgba(11, 11, 15, 0.96) 100%
            ),
            radial-gradient(120% 70% at 70% 0%, rgba(229, 9, 20, 0.28), transparent 50%);
    }
    .hero__slide {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        min-height: 100%;
        overflow: hidden;
        will-change: opacity;
    }
    /* Uniquement les slides carrousel plein écran (pas la grille masonry) */
    .hero__slide[data-slide-type="image"] > img {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        min-width: 100%;
        min-height: 100%;
        object-fit: cover;
        object-position: center;
        display: block;
    }
    .hero__slide-video {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        overflow: hidden;
        background: #000;
    }
    .hero__slide-video .hero__video {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        object-position: center;
        display: block;
        pointer-events: none;
        background: #000;
    }
    .hero__slide[data-slide-type="masonry"] {
        background: #0b0b0f;
    }
    .hero__masonry-viewport {
        position: absolute;
        top: 0;
        bottom: 0;
        left: 50%;
        width: 100vw;
        max-width: none;
        margin-left: -50vw;
        overflow: hidden;
        background: #0b0b0f;
        box-sizing: border-box;
    }
    .hero__masonry-track {
        display: block;
        width: 100%;
        min-width: 100%;
        max-width: none;
        box-sizing: border-box;
        will-change: transform;
    }
    /*
     * Masonry : Macy.js pose les enfants en absolute + hauteur du conteneur.
     * Pas de CSS column-count (cassé avec opacity/autoAlpha sur les slides).
     */
    .hero__masonry-grid {
        position: relative;
        width: 100%;
        min-width: 100%;
        box-sizing: border-box;
        padding: 0.65rem clamp(0.65rem, 3vw, 1.15rem);
    }
    .hero__masonry-item {
        display: block;
        margin: 0;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 8px 28px rgba(0, 0, 0, 0.48);
        background: #14141f;
        box-sizing: border-box;
    }
    .hero__masonry-item img {
        position: static;
        inset: auto;
        width: 100%;
        height: auto;
        max-height: min(32vh, 380px);
        min-width: 0;
        min-height: 0;
        display: block;
        object-fit: cover;
        object-position: center;
        vertical-align: middle;
    }
    @media (max-width: 900px) {
        .hero__masonry-grid {
            padding: 0.55rem;
        }
        .hero__masonry-item img {
            max-height: min(26vh, 260px);
        }
    }
    @media (max-width: 600px) {
        .hero__masonry-item img {
            max-height: min(22vh, 200px);
        }
    }
    .hero__inner {
        position: relative;
        z-index: 2;
        max-width: 52rem;
    }
    .hero .hero__title {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 0.1rem;
        margin: 0 0 0.75rem;
        line-height: 1;
    }
    .hero .hero__title-line {
        display: block;
        max-width: 100%;
        line-height: 1;
        text-shadow: 0 2px 24px rgba(0, 0, 0, 0.45);
    }
    .hero .hero__title-line--levites {
        font-family: "Cooper Hewitt", "Montserrat", ui-sans-serif, sans-serif;
        font-weight: 600;
        font-size: clamp(1.35rem, 3.8vw, 1.85rem);
        letter-spacing: 0.08em;
        color: #a8a7a7;
        text-transform: uppercase;
    }
    .hero .hero__title-line--bonsoir {
        font-family: "Bebas Neue", Impact, "Arial Narrow", sans-serif;
        font-weight: 400;
        font-size: clamp(1.85rem, 5.8vw, 2.75rem);
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: #ffffff;
    }
    .hero p {
        max-width: 36rem;
        color: #e5e7eb;
        margin: 0 0 1.5rem;
        font-size: 1.05rem;
        line-height: 1.55;
        text-shadow: 0 1px 12px rgba(0, 0, 0, 0.5);
    }
    .hero-actions { display: flex; flex-wrap: wrap; gap: 0.75rem; }
    /* État initial avant GSAP (évite le flash de contenu) */
    .hero.hero--gsap:not(.hero--revealed) .hero__title-line,
    .hero.hero--gsap:not(.hero--revealed) .hero__lead,
    .hero.hero--gsap:not(.hero--revealed) .hero-actions .btn {
        opacity: 0;
        transform: translateY(1.25rem);
    }
    @media (prefers-reduced-motion: reduce) {
        .hero.hero--gsap:not(.hero--revealed) .hero__title-line,
        .hero.hero--gsap:not(.hero--revealed) .hero__lead,
        .hero.hero--gsap:not(.hero--revealed) .hero-actions .btn {
            opacity: 1;
            transform: none;
        }
    }
    @supports not (selector(:has(*))) {
        .hero.hero--full {
            min-height: 100vh;
        }
    }
</style>
@endpush

@section('content')
    <section class="hero hero--full hero--gsap" data-hero-section>
        @if (count($carouselImages) > 0 || ($heroVideo ?? null) || count($masonryImages) > 0)
            <div
                class="hero__media hero__media--carousel"
                data-hero-carousel
                data-interval="5800"
                data-video-interval="12000"
                data-masonry-interval="{{ $masonryDurationMs ?? 14000 }}"
                aria-hidden="true"
            >
                @foreach ($carouselImages as $index => $slide)
                    <div
                        class="hero__slide"
                        data-slide-type="image"
                        data-slide-index="{{ $index }}"
                    >
                        <img
                            src="{{ $slide['src'] }}"
                            alt=""
                            width="1920"
                            height="1080"
                            decoding="async"
                            @if ($index === 0) fetchpriority="high" @endif
                            @if ($index > 0) loading="lazy" @endif
                        >
                    </div>
                @endforeach

                @if ($heroVideo ?? null)
                    <div
                        class="hero__slide"
                        data-slide-type="video"
                        data-video-src="{{ $heroVideo }}"
                    >
                        <div class="hero__slide-video"></div>
                    </div>
                @endif

                @if (count($masonryImages) > 0)
                    <div class="hero__slide" data-slide-type="masonry">
                        <div class="hero__masonry-viewport">
                            <div class="hero__masonry-track">
                                {{--
                                  Une seule grille Macy : layout continu (pas de nouvelle ligne droite entre blocs).
                                  La séquence répète tout le pool plusieurs fois avec ordre aléatoire à chaque passage.
                                --}}
                                <div class="hero__masonry-grid" data-macy-root>
                                    @foreach ($masonryFigureSequence as $img)
                                        <figure class="hero__masonry-item" data-masonry-item>
                                            <img
                                                src="{{ $img['src'] }}"
                                                alt=""
                                                width="640"
                                                height="480"
                                                loading="eager"
                                                decoding="async"
                                                fetchpriority="low"
                                            >
                                        </figure>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        @endif
        <div class="hero__scrim" aria-hidden="true"></div>
        <div class="hero__inner" data-hero-content>
            <h1 class="hero__title">
                <span class="hero__title-line hero__title-line--levites">Nuit des Lévites</span>
                <span class="hero__title-line hero__title-line--bonsoir">Bonsoir Saint-Esprit</span>
            </h1>
            <p class="hero__lead">
                Regarde le live sur une expérience type streaming, et soutiens l’événement
                en ligne, en toute sécurité.
            </p>
            <div class="hero-actions">
                <a class="btn btn-primary" href="{{ route('live.show') }}"><i data-lucide="circle-play" aria-hidden="true"></i> Live</a>
                <a class="btn btn-gold" href="{{ route('donations.index') }}"><i data-lucide="heart" aria-hidden="true"></i> Soutenir</a>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    @vite(['resources/js/hero-carousel.js', 'resources/js/home-hero-animations.js', 'resources/js/page-loader.js'])
@endpush
