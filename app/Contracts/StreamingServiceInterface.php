<?php

namespace App\Contracts;

interface StreamingServiceInterface
{
    public function providerKey(): string;

    public function providerLabel(): string;

    /** @return 'iframe'|'hls' */
    public function playbackMode(): string;

    public function isConfigured(): bool;

    public function resolveLiveInputUid(): ?string;

    public function iframeSrc(): ?string;

    public function playbackUrl(): ?string;

    /** Le flux est actuellement diffusé (OBS connecté, etc.). */
    public function isBroadcasting(): bool;

    /**
     * @return array<string, mixed>|null
     */
    public function getLiveInput(?string $uid = null): ?array;

    /**
     * @return array<string, mixed>
     */
    public function createLiveInput(string $name = 'Live Stream'): array;

    /**
     * @return array<string, mixed>|null
     */
    public function setLiveInputEnabled(bool $enabled, ?string $uid = null): ?array;

    /**
     * @return array{rtmps_url: ?string, stream_key: ?string, srt_url: ?string, playback: ?string, enabled: ?bool, status: ?string, uid: ?string}
     */
    public function getIngestInfo(?string $uid = null): array;

    /**
     * @return array<string, mixed>|null
     */
    public function fetchLiveInputStatus(): ?array;
}
