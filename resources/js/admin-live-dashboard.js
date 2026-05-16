function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

function showToast(message, variant = 'success') {
    const el = document.getElementById('live-admin-toast');
    if (!el) {
        return;
    }

    el.textContent = message;
    el.dataset.variant = variant;
    el.hidden = false;
    window.clearTimeout(el._hideTtl);
    el._hideTtl = window.setTimeout(() => {
        el.hidden = true;
    }, 5200);
}

function setShellBusy(on) {
    const root = document.getElementById('live-admin-root');
    const loader = document.getElementById('live-admin-loader');
    if (loader) {
        loader.hidden = !on;
    }

    if (root) {
        root.classList.toggle('is-live-admin-busy', Boolean(on));
    }
}

function parseErrorMessage(data) {
    if (data?.errors && typeof data.errors === 'object') {
        const first = Object.values(data.errors)[0];
        if (Array.isArray(first) && first[0]) {
            return first[0];
        }
    }

    return data?.message || 'Une erreur est survenue.';
}

async function refreshDashboardFragments(html) {
    const root = document.getElementById('live-admin-root');
    if (!root || !html) {
        return;
    }

    root.innerHTML = html;
    window.refreshStreamIcons?.();

    const { initLiveStatusPoll } = await import('./live-status-poll-core.js');
    initLiveStatusPoll(root);

    const { initLivePlayer } = await import('./live-player.js');
    initLivePlayer(root);
}

document.addEventListener('submit', async (e) => {
    const form = e.target.closest('[data-admin-live-form]');
    if (!form || !document.getElementById('live-admin-root')?.contains(form)) {
        return;
    }

    e.preventDefault();

    const confirmMsg = form.dataset.confirm;
    if (confirmMsg && !window.confirm(confirmMsg)) {
        return;
    }

    const submitButtons = [...form.querySelectorAll('[type="submit"]')];
    const prevDisabled = submitButtons.map((btn) => btn.disabled);

    submitButtons.forEach((btn) => {
        btn.disabled = true;
        btn.classList.add('is-loading');
    });

    setShellBusy(true);

    try {
        const res = await fetch(form.action, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken(),
            },
            body: new FormData(form),
            credentials: 'same-origin',
        });

        let data = {};
        try {
            data = await res.json();
        } catch {
            data = {};
        }

        if (res.status === 419) {
            showToast('Session expirée. Rechargez la page.', 'error');

            return;
        }

        if (!res.ok || data.ok === false) {
            showToast(parseErrorMessage(data), 'error');

            return;
        }

        showToast(data.message || 'Enregistré.', 'success');

        if (form.hasAttribute('data-admin-live-full-reload')) {
            window.setTimeout(() => {
                window.location.reload();
            }, 400);

            return;
        }

        if (data.fragments?.dashboard) {
            await refreshDashboardFragments(data.fragments.dashboard);
        }
    } catch {
        showToast('Réseau indisponible.', 'error');
    } finally {
        setShellBusy(false);
        submitButtons.forEach((btn, i) => {
            btn.classList.remove('is-loading');
            btn.disabled = prevDisabled[i];
        });
    }
});

document.addEventListener('click', async (e) => {
    const refreshBtn = e.target.closest('[data-live-preview-refresh]');
    const root = document.getElementById('live-admin-root');
    if (!refreshBtn || !root?.contains(refreshBtn)) {
        return;
    }

    const section = refreshBtn.closest('[data-live-preview-status-url]');
    const url = section?.dataset.livePreviewStatusUrl;
    const player = section?.querySelector('.admin-live-preview-player');
    if (!url || !player) {
        return;
    }

    refreshBtn.disabled = true;
    refreshBtn.classList.add('is-loading');

    try {
        const { fetchLiveStatus, applyLiveStatusPayload, initLiveStatusPoll } = await import('./live-status-poll-core.js');
        const data = await fetchLiveStatus(url);
        const isLive = await applyLiveStatusPayload(player, data);

        if (!isLive && !player.hasAttribute('data-live-poll')) {
            player.setAttribute('data-live-poll', '');
            player.dataset.liveStatusUrl = url;
            player.dataset.livePollInterval = player.dataset.livePollInterval || '4000';
            player.removeAttribute('data-live-poll-armed');
            initLiveStatusPoll(root);
        }

        showToast('Prévisualisation actualisée.', 'success');
    } catch {
        showToast('Impossible d’actualiser la prévisualisation.', 'error');
    } finally {
        refreshBtn.disabled = false;
        refreshBtn.classList.remove('is-loading');
    }
});

document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-copy]');
    const root = document.getElementById('live-admin-root');
    if (!btn || !root?.contains(btn)) {
        return;
    }

    const sel = btn.getAttribute('data-copy');
    const el = sel ? document.querySelector(sel) : null;
    if (!el) {
        return;
    }

    const text = el.textContent.trim();
    navigator.clipboard.writeText(text).then(() => {
        const label = btn.textContent;
        btn.textContent = 'Copié !';
        window.setTimeout(() => {
            btn.textContent = label;
        }, 1500);
    });
});

async function bootLiveAdminDashboard() {
    if (!document.getElementById('live-admin-root')) {
        return;
    }

    window.refreshStreamIcons?.();

    const root = document.getElementById('live-admin-root');
    const { initLiveStatusPoll } = await import('./live-status-poll-core.js');
    initLiveStatusPoll(root);

    const { initLivePlayer } = await import('./live-player.js');
    initLivePlayer(root);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootLiveAdminDashboard);
} else {
    bootLiveAdminDashboard();
}
