import videojs from 'video.js';
import fr from 'video.js/dist/lang/fr.json';
import { registerIVSQualityPlugin, registerIVSTech } from 'amazon-ivs-player';
import wasmWorkerUrl from 'amazon-ivs-player/dist/assets/amazon-ivs-wasmworker.min.js?url';
import wasmBinaryUrl from 'amazon-ivs-player/dist/assets/amazon-ivs-wasmworker.min.wasm?url';
import 'video.js/dist/video-js.css';
import '../css/live-player-videojs.css';

videojs.addLanguage('fr', fr);

/** Tech IVS enregistré une seule fois (WebAssembly + worker packagés par Vite). */
let ivsTechReady = false;

try {
    registerIVSTech(videojs, {
        wasmWorker: wasmWorkerUrl,
        wasmBinary: wasmBinaryUrl,
    });
    registerIVSQualityPlugin(videojs);
    ivsTechReady = true;
} catch (error) {
    console.warn('[live-player] Amazon IVS Tech indisponible, lecture Html5+VHS.', error);
}

/**
 * Détruit les instances Video.js dont le DOM est sous `container` (avant innerHTML).
 *
 * @param {ParentNode|null} container
 */
export function disposeLivePlayersIn(container) {
    if (!container?.querySelector) {
        return;
    }

    const players = videojs.getPlayers();
    const ids = Object.keys(players);

    ids.forEach((id) => {
        const player = players[id];

        try {
            if (!player || player.isDisposed()) {
                return;
            }

            const el = player.el();
            if (el && container.contains(el)) {
                player.dispose();
            }
        } catch {
            /* ignore */
        }
    });
}

/**
 * @param {HTMLVideoElement} videoEl
 */
function attachLiveVideoJs(videoEl) {
    const src = videoEl.dataset.src;

    if (!src || videoEl.dataset.liveVideojsDone === '1') {
        return;
    }

    videoEl.dataset.liveVideojsDone = '1';

    const mobileLike =
        typeof window.matchMedia === 'function' &&
        (window.matchMedia('(max-width: 896px)').matches ||
            window.matchMedia('(hover: none) and (pointer: coarse)').matches);

    const player = videojs(videoEl, {
        ...(ivsTechReady ? { techOrder: ['AmazonIVS', 'html5'] } : {}),
        controls: true,
        autoplay: 'muted',
        preload: 'auto',
        playsinline: true,
        fluid: false,
        fill: true,
        language: 'fr',
        liveui: true,
        /* Mobile : barre toujours « active » (évite opacity 0 sans survol). */
        inactivityTimeout: mobileLike ? 0 : 4000,
        /**
         * Fenêtre seekable IVs souvent courte — seuil bas pour afficher la barre live UI.
         */
        liveTracker: {
            trackingThreshold: 1,
        },
        html5: {
            vhs: {
                overrideNative: true,
                enableLowInitialPlaylist: true,
            },
            nativeAudioTracks: false,
            nativeVideoTracks: false,
        },
        controlBar: {
            pictureInPictureToggle: false,
        },
    });

    player.src({ src, type: 'application/x-mpegURL' });

    player.ready(() => {
        if (typeof player.enableIVSQualityPlugin === 'function') {
            player.enableIVSQualityPlugin();
        }

        if (mobileLike) {
            player.userActive(true);
            player.on('touchstart', () => player.userActive(true));
            player.on('playing', () => player.userActive(true));
        }

        player.play()?.catch(() => {
            /* autoplay peut être bloqué avant interaction */
        });
    });
}

/**
 * @param {ParentNode} [scope=document]
 */
export function initLivePlayer(scope = document) {
    scope.querySelectorAll('.live-player-netflix video.video-js[data-src]').forEach((videoEl) => {
        attachLiveVideoJs(videoEl);
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => initLivePlayer(document));
} else {
    initLivePlayer(document);
}
