(() => {
    const el = document.getElementById('page-load-overlay');
    if (!el) return;

    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    let done = false;

    const hide = () => {
        if (done) return;
        done = true;
        el.setAttribute('aria-busy', 'false');
        el.classList.add('page-load-overlay--hidden');

        const remove = () => {
            el.remove();
        };

        if (reduced) {
            remove();
            return;
        }

        el.addEventListener(
            'transitionend',
            (e) => {
                if (e.propertyName === 'opacity') remove();
            },
            { once: true },
        );

        window.setTimeout(remove, 500);
    };

    window.addEventListener('load', hide, { once: true });
    window.setTimeout(hide, 15000);
})();
