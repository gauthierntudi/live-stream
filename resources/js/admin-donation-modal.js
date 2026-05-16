function initDonationModal() {
    const dialog = document.getElementById('donation-modal');
    const body = document.getElementById('donation-modal-body');
    if (!dialog || !body) {
        return;
    }

    const titleEl = document.getElementById('donation-modal-title');
    let activeTrigger = null;

    const close = () => {
        dialog.close();
        body.innerHTML = '';
        dialog.classList.remove('is-loading');
        if (activeTrigger) {
            activeTrigger.focus();
            activeTrigger = null;
        }
    };

    const open = async (url, trigger) => {
        activeTrigger = trigger;
        dialog.classList.add('is-loading');
        body.innerHTML = '<p class="donation-modal__loading">Chargement…</p>';
        dialog.showModal();

        try {
            const response = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'text/html',
                },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                throw new Error('fetch_failed');
            }

            body.innerHTML = await response.text();
            if (titleEl) {
                titleEl.textContent = 'Détail du paiement';
            }

            if (typeof window.refreshStreamIcons === 'function') {
                window.refreshStreamIcons();
            }
        } catch {
            body.innerHTML =
                '<p class="donation-modal__error">Impossible de charger le détail. Réessayez.</p>';
        } finally {
            dialog.classList.remove('is-loading');
        }
    };

    document.querySelectorAll('[data-donation-modal]').forEach((btn) => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const url = btn.getAttribute('data-donation-modal');
            if (url) {
                open(url, btn);
            }
        });
    });

    dialog.querySelectorAll('[data-donation-modal-close]').forEach((el) => {
        el.addEventListener('click', close);
    });

    dialog.addEventListener('click', (e) => {
        if (e.target === dialog) {
            close();
        }
    });

    dialog.addEventListener('cancel', (e) => {
        e.preventDefault();
        close();
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initDonationModal);
} else {
    initDonationModal();
}
