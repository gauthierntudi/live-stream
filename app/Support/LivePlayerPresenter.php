<?php

namespace App\Support;

use App\Contracts\StreamingServiceInterface;
use App\Models\Setting;

final class LivePlayerPresenter
{
    public function __construct(
        private StreamingServiceInterface $stream,
    ) {}

    /**
     * Arrière-plan « écran d’attente » (même logique que la vue live).
     */
    public function waitingBackgroundUrl(): ?string
    {
        $bgConfig = config('app.donation_success_background');
        if (is_string($bgConfig) && trim($bgConfig) !== '') {
            $t = trim($bgConfig);

            return str_starts_with($t, 'http://') || str_starts_with($t, 'https://')
                ? $t
                : asset(ltrim($t, '/'));
        }

        foreach (['webp', 'jpg', 'jpeg', 'png'] as $ext) {
            if (is_file(public_path('img/success-bg.'.$ext))) {
                return asset('img/success-bg.'.$ext);
            }
        }

        return null;
    }

    /**
     * Payload JSON pour `/live/status` et la prévisualisation admin (polling sans recharger).
     *
     * @return array{
     *     live: bool,
     *     hasPlayerConfig: bool,
     *     playbackMode: string,
     *     html: ?string,
     *     playerStateHash: string,
     *     showWaiting: bool,
     *     waitingBgUrl: ?string,
     * }
     */
    public function statusJsonPayload(bool $requirePublicVisibility): array
    {
        $data = $this->viewData($requirePublicVisibility);
        $html = ($data['hasPlayerConfig'] ?? false)
            ? view('live.partials.player-inner', $data)->render()
            : null;

        return [
            'live' => $data['streamLive'],
            'hasPlayerConfig' => $data['hasPlayerConfig'],
            'playbackMode' => $data['playbackMode'],
            'html' => $html,
            'playerStateHash' => is_string($html) && $html !== '' ? hash('xxh128', $html) : '',
            'showWaiting' => $data['showWaiting'],
            'waitingBgUrl' => $data['waitingBgUrl'],
        ];
    }

    /**
     * @return array{
     *     iframeSrc: ?string,
     *     playbackUrl: ?string,
     *     playbackMode: string,
     *     streamLive: bool,
     *     hasPlayerConfig: bool,
     *     youtubeAwaitingEmbed: bool,
     *     isHls: bool,
     *     showPlayer: bool,
     *     showWaiting: bool,
     *     broadcasting: bool,
     *     publicLiveVisible: bool,
     *     waitingNotYetPublic: bool,
     *     waitingBgUrl: ?string,
     * }
     */
    public function viewData(bool $requirePublicVisibility): array
    {
        $iframeSrc = $this->stream->iframeSrc();
        $playbackUrl = $this->stream->playbackUrl();
        $hasPlaybackSurface = $iframeSrc !== null || $playbackUrl !== null;
        $hasIngestIdentity = $this->stream->resolveLiveInputUid() !== null;
        /*
         * Inclure le canal / entrée (ARN IVS, UID Cloudflare, vidéo YouTube) même si l’URL de lecture
         * n’est pas encore résolue : sinon la page publique affiche « Live non disponible » au lieu de l’attente.
         * YouTube : tant que ce fournisseur est actif, on garde l’UX « stream » (overlay d’attente) même avant
         * la saisie de l’ID / URL dans l’admin.
         */
        $hasPlayerConfig = $hasPlaybackSurface
            || $hasIngestIdentity
            || $this->stream->providerKey() === 'youtube';
        $broadcasting = $this->stream->isBroadcasting();
        $publicVisible = Setting::isLivePublicVisible();
        $playbackMode = $this->stream->playbackMode();
        $isHls = $playbackMode === 'hls';

        /*
         * Public : dès que l’admin a ouvert au public et qu’une URL de lecture existe, on garde le lecteur
         * affiché (évite le « flash » puis disparition quand isBroadcasting() fluctue ou avant qu’OBS envoie).
         * Admin / prévisualisation : le lecteur suit toujours l’état réel du signal.
         */
        if ($requirePublicVisibility) {
            $showPlayer = $hasPlaybackSurface && $publicVisible;
            $streamLive = $showPlayer;
        } else {
            $streamLive = $broadcasting;
            $showPlayer = $hasPlaybackSurface && $streamLive;
        }

        $showWaiting = $hasPlayerConfig && ! $showPlayer;
        $waitingNotYetPublic = $requirePublicVisibility
            && $broadcasting
            && ! $publicVisible
            && $hasPlayerConfig;

        $youtubeAwaitingEmbed = $this->stream->providerKey() === 'youtube' && ! $hasPlaybackSurface;

        return [
            'iframeSrc' => $iframeSrc,
            'playbackUrl' => $playbackUrl,
            'playbackMode' => $playbackMode,
            'streamLive' => $streamLive,
            'hasPlayerConfig' => $hasPlayerConfig,
            'youtubeAwaitingEmbed' => $youtubeAwaitingEmbed,
            'isHls' => $isHls,
            'showPlayer' => $showPlayer,
            'showWaiting' => $showWaiting,
            'broadcasting' => $broadcasting,
            'publicLiveVisible' => $publicVisible,
            'waitingNotYetPublic' => $waitingNotYetPublic,
            'waitingBgUrl' => $this->waitingBackgroundUrl(),
        ];
    }
}
