import gsap from 'gsap';

/**
 * Entrée du hero accueil : titre, texte, boutons (+ nav légère).
 */
function initHomeHeroAnimations() {
    const hero = document.querySelector('[data-hero-section]');
    if (!hero) {
        return;
    }

    const content = hero.querySelector('[data-hero-content]');
    if (!content) {
        return;
    }

    const titleLines = content.querySelectorAll('.hero__title-line');
    const lead = content.querySelector('.hero__lead');
    const buttons = content.querySelectorAll('.hero-actions .btn');
    const scrim = hero.querySelector('.hero__scrim');
    const nav = document.querySelector('.nav');

    const revealStatic = () => {
        hero.classList.add('hero--revealed');
        gsap.set([titleLines, lead, buttons, scrim, nav], { clearProps: 'all' });
    };

    const reduced =
        typeof window.matchMedia === 'function' &&
        window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (reduced) {
        revealStatic();

        return;
    }

    gsap.set(scrim, { autoAlpha: 0.72 });
    gsap.set(titleLines, { y: 32, autoAlpha: 0 });
    if (lead) {
        gsap.set(lead, { y: 22, autoAlpha: 0 });
    }
    gsap.set(buttons, { y: 18, autoAlpha: 0 });
    if (nav) {
        gsap.set(nav, { y: -14, autoAlpha: 0 });
    }

    const tl = gsap.timeline({
        defaults: { ease: 'power3.out' },
        onComplete: revealStatic,
    });

    if (nav) {
        tl.to(nav, { y: 0, autoAlpha: 1, duration: 0.65 }, 0.08);
    }

    tl.to(scrim, { autoAlpha: 1, duration: 0.85, ease: 'power2.inOut' }, 0);

    tl.to(
        titleLines,
        {
            y: 0,
            autoAlpha: 1,
            duration: 0.9,
            stagger: { each: 0.14, from: 'start' },
        },
        0.22,
    );

    if (lead) {
        tl.to(lead, { y: 0, autoAlpha: 1, duration: 0.78 }, 0.52);
    }

    tl.to(
        buttons,
        {
            y: 0,
            autoAlpha: 1,
            duration: 0.68,
            stagger: 0.09,
            ease: 'back.out(1.4)',
        },
        0.68,
    );

    // Filet de sécurité si GSAP est interrompu
    window.setTimeout(revealStatic, 4000);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initHomeHeroAnimations);
} else {
    initHomeHeroAnimations();
}
