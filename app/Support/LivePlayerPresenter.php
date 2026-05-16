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
        $streamLive = $requirePublicVisibility
            ? ($broadcasting && $publicVisible)
            : $broadcasting;
        $playbackMode = $this->stream->playbackMode();
        $isHls = $playbackMode === 'hls';
        $showPlayer = $hasPlaybackSurface && $streamLive;
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
        ];
    }
}
