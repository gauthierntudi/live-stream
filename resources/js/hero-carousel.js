import gsap from 'gsap';
import { killMasonryScroll, startMasonryScroll } from './hero-masonry.js';

const reducedMotionQuery =
    typeof window.matchMedia === 'function'
        ? window.matchMedia('(prefers-reduced-motion: reduce)')
        : null;

function prefersReducedMotion() {
    return reducedMotionQuery?.matches ?? false;
}

/**
 * Stoppe le tween Ken Burns sans réinitialiser le scale — évite le « dézoom » brutal
 * au moment où la slide commence à s’estomper (clearProps ramenait tout de suite à scale 1).
 */
function haltKenBurnsTween(slide) {
    slide._kenBurnsTween?.kill();
    slide._kenBurnsTween = null;
}

/**
 * Arrêt complet + retour aux styles par défaut (nouvelle entrée sur la slide image).
 *
 * @param {HTMLElement} slide
 */
function killKenBurns(slide) {
    haltKenBurnsTween(slide);
    const img = slide.querySelector('img');
    if (img) {
        gsap.set(img, { clearProps: 'scale,transform' });
    }
}

/**
 * @param {HTMLElement} slide
 * @param {number} durationMs
 */
function startKenBurns(slide, durationMs) {
    if (prefersReducedMotion() || slide.dataset.slideType !== 'image') {
        return;
    }

    const img = slide.querySelector('img');
    if (!img) {
        return;
    }

    killKenBurns(slide);
    /*
     * Éviter fromTo(>1 → 1) : GSAP applique tout de suite la valeur de départ → zoom brutal puis dézoom doux.
     * Partir explicitement de 1 et monter très lentement → mouvement continu sans à-coup.
     */
    gsap.set(img, {
        scale: 1,
        transformOrigin: '50% 45%',
        force3D: true,
    });
    slide._kenBurnsTween = gsap.to(img, {
        scale: 1.038,
        duration: durationMs / 1000,
        ease: 'none',
    });
}

/**
 * Diaporama hero : images → vidéo → grille masonry (défilement vertical), en boucle.
 */
function mountHeroCarousel(root) {
    const slides = Array.from(root.querySelectorAll('.hero__slide'));
    if (slides.length === 0) {
        return;
    }

    const imageMs = Number(root.dataset.interval) || 5800;
    const videoMs =
        Number(root.dataset.videoInterval) ||
        Number(root.dataset.youtubeInterval) ||
        12000;
    const masonryMs = Number(root.dataset.masonryInterval) || 14000;
    const reduced = prefersReducedMotion();
    let current = 0;
    let timerId = null;
    let animating = false;

    const slideDuration = (slide) => {
        const type = slide.dataset.slideType;

        if (type === 'video') {
            return videoMs;
        }

        if (type === 'masonry') {
            return masonryMs;
        }

        return imageMs;
    };

    gsap.set(slides, {
        autoAlpha: 0,
        pointerEvents: 'none',
        zIndex: 0,
    });
    gsap.set(slides[0], {
        autoAlpha: 1,
        pointerEvents: 'auto',
        zIndex: 1,
    });

    const videoHost = (slide) => slide.querySelector('.hero__slide-video');

    const injectHeroVideo = (slide) => {
        const host = videoHost(slide);
        if (!host || host.querySelector('video')) {
            return;
        }
        const src = slide.dataset.videoSrc;
        if (!src) {
            return;
        }
        const video = document.createElement('video');
        video.className = 'hero__video';
        video.muted = true;
        video.defaultMuted = true;
        video.autoplay = true;
        video.loop = true;
        video.playsInline = true;
        video.setAttribute('playsinline', '');
        video.setAttribute('preload', 'auto');
        video.setAttribute('aria-hidden', 'true');
        video.src = src;
        host.appendChild(video);
        video.play().catch(() => {});
    };

    const teardownHeroVideo = (slide) => {
        const host = videoHost(slide);
        if (!host) {
            return;
        }
        const video = host.querySelector('video');
        if (video) {
            video.pause();
            video.removeAttribute('src');
            video.load();
        }
        host.innerHTML = '';
    };

    const activateSlide = (slide) => {
        const type = slide.dataset.slideType;
        const duration = slideDuration(slide);

        if (type === 'video') {
            injectHeroVideo(slide);
        } else if (type === 'masonry') {
            startMasonryScroll(slide, duration);
        } else {
            startKenBurns(slide, duration);
        }
    };

    const deactivateSlide = (slide) => {
        const type = slide.dataset.slideType;

        if (type === 'video') {
            teardownHeroVideo(slide);
        } else if (type === 'masonry') {
            killMasonryScroll(slide);
        } else {
            /*
             * Image : on ne fait pas clearProps ici — sinon zoom figé → snap à scale 1 au début du fondu (effet dézoom).
             * Le reset se fait une fois la slide masquée (onComplete) ou au prochain startKenBurns.
             */
            haltKenBurnsTween(slide);
        }
    };

    activateSlide(slides[0]);

    const scheduleNext = () => {
        if (timerId) {
            clearTimeout(timerId);
        }
        if (slides.length <= 1) {
            return;
        }
        const ms = slideDuration(slides[current]);
        timerId = window.setTimeout(() => {
            go((current + 1) % slides.length);
        }, ms);
    };

    const go = (nextIndex) => {
        if (animating || slides.length <= 1) {
            return;
        }
        if (nextIndex === current) {
            return;
        }

        const prev = slides[current];
        const next = slides[nextIndex];

        animating = true;
        if (timerId) {
            clearTimeout(timerId);
        }

        deactivateSlide(prev);

        gsap.set(next, { zIndex: 2, pointerEvents: 'auto' });

        const tl = gsap.timeline({
            defaults: { ease: 'power3.inOut' },
            onComplete: () => {
                gsap.set(prev, { zIndex: 0, pointerEvents: 'none' });
                gsap.set(next, { zIndex: 1 });

                if (prev.dataset.slideType === 'image') {
                    const prevSlideImg = prev.querySelector('img');
                    if (prevSlideImg) {
                        gsap.set(prevSlideImg, { clearProps: 'scale,transform' });
                    }
                }

                /*
                 * Macy ignore les enfants dont offsetParent === null (slide encore masquée).
                 * Le slide masonry est donc initialisé plus tôt dans la timeline (voir ci-dessous).
                 */
                const imageKenBurnsStartedEarly =
                    next.dataset.slideType === 'image' && !reduced;

                if (next.dataset.slideType !== 'masonry' && !imageKenBurnsStartedEarly) {
                    activateSlide(next);
                }
                current = nextIndex;
                animating = false;
                scheduleNext();
            },
        });

        const fadeDuration = reduced ? 0.45 : 0.78;
        const fadeInDuration = reduced ? 0.5 : 0.95;
        const fadeInStart = reduced ? 0.06 : 0.14;

        tl.to(prev, { autoAlpha: 0, duration: fadeDuration }, 0);

        tl.fromTo(
            next,
            { autoAlpha: 0 },
            { autoAlpha: 1, duration: fadeInDuration },
            fadeInStart,
        );

        /*
         * Ken Burns image : démarre pendant le fondu (voir tl.call) avec durée ajustée jusqu’au slide suivant,
         * zoom léger 1 → ~1.04 sans valeur initiale « sauté » (plus de fromTo depuis scale > 1).
         */
        if (!reduced && next.dataset.slideType === 'image') {
            const transitionEndSec = Math.max(
                fadeDuration,
                fadeInStart + fadeInDuration,
            );
            const kenInjectSec = fadeInStart + 0.08;
            const slideDurSec = slideDuration(next) / 1000;
            const kenMs = Math.max(
                1600,
                (transitionEndSec + slideDurSec - kenInjectSec) * 1000,
            );
            tl.call(() => startKenBurns(next, kenMs), [], kenInjectSec);
        }

        if (next.dataset.slideType === 'masonry') {
            tl.call(
                () => activateSlide(next),
                [],
                /*
                 * Juste après le début du fade-in : GSAP rend la slide visible (autoAlpha)
                 * avant que Macy ne mesure les enfants.
                 */
                fadeInStart + 0.05,
            );
        }
    };

    if (slides.length > 1) {
        scheduleNext();
    }
}

document.querySelectorAll('[data-hero-carousel]').forEach((el) => {
    mountHeroCarousel(el);
});
