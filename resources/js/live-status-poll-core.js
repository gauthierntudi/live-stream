const DEFAULT_INTERVAL_MS = 8000;

function getPollIntervalMs(player) {
    const raw = player?.dataset.livePollInterval;
    const parsed = raw ? Number.parseInt(raw, 10) : NaN;

    return Number.isFinite(parsed) && parsed >= 3000 ? parsed : DEFAULT_INTERVAL_MS;
}

export async function fetchLiveStatus(statusUrl) {
    const response = await fetch(statusUrl, {
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
    });

    if (!response.ok) {
        throw new Error(`Live status HTTP ${response.status}`);
    }

    return response.json();
}

async function initHlsIfNeeded(playbackMode, scope) {
    if (playbackMode !== 'hls') {
        return;
    }

    const { initLivePlayer } = await import('./live-player.js');
    initLivePlayer(scope);
}

function syncPlayerWaitBackdrop(player, data) {
    const url = data.waitingBgUrl;

    if (data.live) {
        player.classList.remove('player--has-wait-bg');
        player.removeAttribute('style');

        return;
    }

    if (data.showWaiting && typeof url === 'string' && url !== '') {
        player.classList.add('player--has-wait-bg');
        player.style.setProperty('--live-wait-bg', `url(${JSON.stringify(url)})`);

        return;
    }

    player.classList.remove('player--has-wait-bg');
    player.removeAttribute('style');
}

/**
 * Met à jour le lecteur à partir de la réponse JSON /live/status ou preview-status.
 * Évite de réinjecter le même HTML en boucle (hash) pour ne pas couper la vidéo IVS / iframe.
 *
 * @returns {Promise<boolean>} état « flux jouable » (streamLive côté serveur)
 */
export async function applyLiveStatusPayload(player, data) {
    const incomingHash = typeof data.playerStateHash === 'string' ? data.playerStateHash : '';
    const currentHash = player.dataset.liveContentHash ?? '';
    const unchanged = incomingHash !== '' && incomingHash === currentHash;

    if (!unchanged && typeof data.html === 'string' && data.html !== '') {
        const { disposeLivePlayersIn } = await import('./live-player.js');
        disposeLivePlayersIn(player);
        player.innerHTML = data.html;
        player.dataset.liveContentHash = incomingHash;
    }

    syncPlayerWaitBackdrop(player, data);

    if (data.live) {
        await initHlsIfNeeded(data.playbackMode, player);
    }

    return Boolean(data.live);
}

function schedulePoll(player, statusUrl, intervalMs) {
    window.setTimeout(() => pollLiveStatus(player, statusUrl, intervalMs), intervalMs);
}

async function pollLiveStatus(player, statusUrl, intervalMs) {
    if (!player.isConnected || !player.hasAttribute('data-live-poll')) {
        return;
    }

    try {
        const data = await fetchLiveStatus(statusUrl);
        await applyLiveStatusPayload(player, data);
    } catch {
        /* réessaie au prochain cycle */
    }

    schedulePoll(player, statusUrl, intervalMs);
}

/**
 * @param {ParentNode} [scope=document]
 */
export function initLiveStatusPoll(scope = document) {
    scope.querySelectorAll('[data-live-poll]').forEach((player) => {
        if (player.dataset.livePollArmed === '1') {
            return;
        }

        player.dataset.livePollArmed = '1';

        const statusUrl = player.dataset.liveStatusUrl;
        if (!statusUrl) {
            return;
        }

        const intervalMs = getPollIntervalMs(player);
        pollLiveStatus(player, statusUrl, intervalMs);
    });
}
