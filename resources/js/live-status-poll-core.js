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

/**
 * Met à jour le contenu du lecteur à partir de la réponse JSON /live/status ou preview-status.
 *
 * @returns {Promise<boolean>} true si le flux est en lecture (arrêt du polling)
 */
export async function applyLiveStatusPayload(player, data) {
    if (data.html) {
        const { disposeLivePlayersIn } = await import('./live-player.js');
        disposeLivePlayersIn(player);
        player.innerHTML = data.html;
    }

    if (data.live) {
        player.classList.remove('player--has-wait-bg');
        player.removeAttribute('style');
        player.removeAttribute('data-live-poll');
        player.removeAttribute('data-live-poll-armed');
        await initHlsIfNeeded(data.playbackMode, player);

        return true;
    }

    return false;
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
        const isLive = await applyLiveStatusPayload(player, data);
        if (isLive) {
            return;
        }
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
