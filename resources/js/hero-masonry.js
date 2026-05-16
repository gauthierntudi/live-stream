import gsap from 'gsap';
import Macy from 'macy';

function prefersReducedMotion() {
    return (
        typeof window.matchMedia === 'function' &&
        window.matchMedia('(prefers-reduced-motion: reduce)').matches
    );
}

/**
 * @param {HTMLElement} grid
 */
function createHeroMacy(grid) {
    return Macy({
        container: grid,
        columns: 6,
        trueOrder: false,
        waitForImages: true,
        useContainerForBreakpoints: true,
        margin: { x: 10, y: 10 },
        breakAt: {
            1320: 5,
            1024: 4,
            768: 3,
            560: 2,
        },
    });
}

/** Hauteur utile du mur masonry (une seule grille). */
function measureMasonryContentHeight(track) {
    const grid = track.querySelector('[data-macy-root]');
    if (!grid) {
        return Math.max(track.scrollHeight, 200);
    }

    const rectH = Math.round(grid.getBoundingClientRect().height);
    return Math.max(rectH, grid.offsetHeight || 0, grid.scrollHeight || 0, 200);
}

/**
 * Distance verticale pour faire défiler tout le contenu au-delà du viewport (sans trou vide excessif).
 *
 * @param {HTMLElement} slide
 * @param {HTMLElement} track
 */
function measureMasonryScrollDistance(slide, track) {
    const contentH = measureMasonryContentHeight(track);
    const viewport = slide.querySelector('.hero__masonry-viewport');
    const vh = viewport?.clientHeight ?? 0;

    if (vh <= 0) {
        return Math.max(contentH * 0.55, 280);
    }

    return Math.max(contentH - vh + 40, Math.min(contentH * 0.4, contentH), 280);
}

/**
 * Arrête masonry + Macy + enlève transforms / opacités inline GSAP sur ce slide.
 *
 * @param {HTMLElement} slide
 */
export function killMasonryScroll(slide) {
    slide._masonryTween?.kill();
    slide._masonryTween = null;

    if (slide._macyInstances?.length) {
        slide._macyInstances.forEach((m) => {
            try {
                m.remove();
            } catch {
                /* noop */
            }
        });
        slide._macyInstances = null;
    }

    const track = slide.querySelector('.hero__masonry-track');
    if (track) {
        gsap.set(track, { clearProps: 'transform' });
    }

    const items = slide.querySelectorAll('[data-masonry-item]');
    if (items.length) {
        gsap.set(items, { clearProps: 'opacity,visibility' });
    }
}

/**
 * Masonry : une seule grille Macy (layout continu), séquence images répétée et mélangée côté serveur.
 *
 * @param {HTMLElement} slide
 * @param {number} durationMs
 */
export function startMasonryScroll(slide, durationMs) {
    const track = slide.querySelector('.hero__masonry-track');

    if (!track) {
        return;
    }

    killMasonryScroll(slide);

    const items = slide.querySelectorAll('[data-masonry-item]');

    const startAnimation = () => {
        void slide.offsetHeight;
        void track.offsetHeight;

        const roots = Array.from(track.querySelectorAll('[data-macy-root]'));
        slide._macyInstances = roots.map((grid) => createHeroMacy(grid));

        const runGsap = () => {
            gsap.set(track, { force3D: false, transformOrigin: '50% 0%', y: 0 });

            const totalDist = measureMasonryScrollDistance(slide, track);

            const reduced = prefersReducedMotion();

            if (reduced) {
                gsap.set(items, { autoAlpha: 1 });

                gsap.set(track, {
                    y: -Math.min(totalDist * 0.22, Math.max(totalDist * 0.1, 80)),
                });

                return;
            }

            gsap.set(items, { autoAlpha: 0 });

            slide._masonryTween = gsap.timeline({
                defaults: {
                    overwrite: true,
                    ease: 'power1.out',
                },
            });

            slide._masonryTween.to(items, {
                autoAlpha: 1,
                duration: 0.42,
                stagger: {
                    each: 0.018,
                    from: 'random',
                },
                ease: 'power1.out',
            });

            /*
             * Défilement linéaire sur la distance utile (contenu − fenêtre) : pas de repeat.
             * Les répétitions d’images sont dans la même grille Macy → pas de « ligne droite » entre blocs.
             */
            const durationSec = Math.max(durationMs / 1000, 12);

            slide._masonryTween.to(
                track,
                {
                    y: -totalDist,
                    duration: durationSec,
                    ease: 'none',
                },
                '>0.04',
            );
        };

        requestAnimationFrame(() => {
            slide._macyInstances.forEach((m) => m.recalculate(true, true));
            requestAnimationFrame(runGsap);
        });
    };

    const images = Array.from(track.querySelectorAll('img'));
    const pending = images.filter((img) => !img.complete);

    if (pending.length === 0) {
        requestAnimationFrame(() => {
            requestAnimationFrame(startAnimation);
        });

        return;
    }

    let done = 0;
    const onDone = () => {
        done += 1;
        if (done >= pending.length) {
            requestAnimationFrame(() => {
                requestAnimationFrame(startAnimation);
            });
        }
    };

    pending.forEach((img) => {
        img.addEventListener('load', onDone, { once: true });
        img.addEventListener('error', onDone, { once: true });
    });
}
