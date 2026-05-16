<?php

namespace App\Services;

use App\Contracts\StreamingServiceInterface;
use App\Models\Setting;
use RuntimeException;

/**
 * Lecture publique via embed YouTube (live ou vidéo). L’ingest se fait dans YouTube Studio / OBS → YouTube.
 */
class YoutubeEmbedStreamService implements StreamingServiceInterface
{
    public function providerKey(): string
    {
        return 'youtube';
    }

    public function providerLabel(): string
    {
        return 'YouTube (embed)';
    }

    public function playbackMode(): string
    {
        return 'iframe';
    }

    public function isConfigured(): bool
    {
        return $this->normalizedVideoId() !== null;
    }

    public function resolveLiveInputUid(): ?string
    {
        return $this->normalizedVideoId();
    }

    public function iframeSrc(): ?string
    {
        $id = $this->normalizedVideoId();
        if ($id === null) {
            return null;
        }

        $explicit = config('streaming.youtube.embed_url');
        if (is_string($explicit) && str_contains($explicit, 'http')) {
            return $this->applyYoutubeEmbedPlaybackParams(trim($explicit));
        }

        return sprintf(
            'https://www.youtube.com/embed/%s?%s',
            rawurlencode($id),
            http_build_query($this->embedPlaybackQueryParams()),
        );
    }

    public function playbackUrl(): ?string
    {
        return $this->iframeSrc();
    }

    /**
     * Pas d’API YouTube : dès qu’un ID est configuré, on considère le lecteur « prêt ».
     * L’iframe YouTube affiche l’état réel (hors ligne, en attente, en direct).
     */
    public function isBroadcasting(): bool
    {
        return $this->isConfigured();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getLiveInput(?string $uid = null): ?array
    {
        $id = $this->normalizedVideoId();
        if ($id === null) {
            return null;
        }

        return [
            'uid' => $id,
            'enabled' => true,
            'status' => [
                'current' => ['state' => 'youtube_embed'],
                'state' => 'youtube_embed',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function createLiveInput(string $name = 'Live Stream'): array
    {
        throw new RuntimeException(
            'YouTube : créez votre diffusion dans YouTube Studio, puis renseignez l’URL ou l’ID vidéo dans l’administration (section YouTube) ou dans YOUTUBE_LIVE_VIDEO_ID (.env).'
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function setLiveInputEnabled(bool $enabled, ?string $uid = null): ?array
    {
        return $this->getLiveInput($uid);
    }

    /**
     * @return array{rtmps_url: ?string, stream_key: ?string, srt_url: ?string, playback: ?string, enabled: ?bool, status: ?string, uid: ?string}
     */
    public function getIngestInfo(?string $uid = null): array
    {
        $id = $this->normalizedVideoId();

        return [
            'rtmps_url' => null,
            'stream_key' => null,
            'srt_url' => null,
            'playback' => $this->iframeSrc(),
            'enabled' => $id !== null,
            'status' => $id !== null ? 'youtube_embed' : null,
            'uid' => $id,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function fetchLiveInputStatus(): ?array
    {
        return null;
    }

    private function normalizedVideoId(): ?string
    {
        $stored = Setting::get(Setting::YOUTUBE_LIVE_VIDEO_ID);
        if (is_string($stored) && trim($stored) !== '') {
            $fromDb = self::normalizeYoutubeVideoId(trim($stored));
            if ($fromDb !== null) {
                return $fromDb;
            }
        }

        $raw = config('streaming.youtube.video_id');

        return self::normalizeYoutubeVideoId(is_string($raw) ? $raw : null);
    }

    /**
     * Paramètres lecture iframe (autoplay : YOUTUBE_EMBED_AUTOPLAY).
     *
     * @return array<string, string>
     */
    private function embedPlaybackQueryParams(): array
    {
        return [
            'autoplay' => config('streaming.youtube.embed_autoplay', true) ? '1' : '0',
            'mute' => config('streaming.youtube.embed_muted', false) ? '1' : '0',
            'playsinline' => '1',
            'rel' => '0',
            'modestbranding' => '1',
        ];
    }

    /**
     * Fusionne autoplay / mute / playsinline sur YOUTUBE_LIVE_EMBED_URL (les réglages .env priment sur la query existante).
     */
    private function applyYoutubeEmbedPlaybackParams(string $url): string
    {
        $parts = parse_url($url);
        if ($parts === false || ! isset($parts['scheme'], $parts['host'])) {
            return $url;
        }

        parse_str($parts['query'] ?? '', $existingQuery);
        $merged = array_merge($existingQuery, $this->embedPlaybackQueryParams());

        $scheme = $parts['scheme'];
        $host = $parts['host'];
        $port = isset($parts['port']) ? ':'.$parts['port'] : '';
        $path = $parts['path'] ?? '';
        $query = http_build_query($merged);
        $fragment = isset($parts['fragment']) ? '#'.$parts['fragment'] : '';

        return "{$scheme}://{$host}{$port}{$path}?{$query}{$fragment}";
    }

    public static function normalizeYoutubeVideoId(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }

        if (preg_match('/^[a-zA-Z0-9_-]{11}$/', $value)) {
            return $value;
        }

        if (preg_match(
            '~(?:youtube\.com/watch\?[^#]*v=|youtu\.be/|youtube\.com/embed/|youtube\.com/live/|youtube\.com/shorts/)([a-zA-Z0-9_-]{11})~',
            $value,
            $m
        )) {
            return $m[1];
        }

        return null;
    }
}
