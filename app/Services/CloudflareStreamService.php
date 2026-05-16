<?php

namespace App\Services;

use App\Contracts\StreamingServiceInterface;
use App\Models\Setting;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class CloudflareStreamService implements StreamingServiceInterface
{
    public const SETTING_LIVE_INPUT_UID = 'cloudflare_live_input_uid';

    public function providerKey(): string
    {
        return 'cloudflare';
    }

    public function providerLabel(): string
    {
        return 'Cloudflare Stream';
    }

    public function playbackMode(): string
    {
        return 'iframe';
    }

    public function isConfigured(): bool
    {
        return $this->accountId() !== null && $this->apiToken() !== null;
    }

    public function resolveLiveInputUid(): ?string
    {
        $stored = Setting::get(self::SETTING_LIVE_INPUT_UID);
        if (is_string($stored) && $stored !== '') {
            return $stored;
        }

        $fromEnv = config('streaming.cloudflare.live_input_uid');

        return is_string($fromEnv) && $fromEnv !== '' ? $fromEnv : null;
    }

    public function playbackUrl(): ?string
    {
        return $this->iframeSrc();
    }

    public function isBroadcasting(): bool
    {
        $input = $this->getLiveInput();
        if ($input === null) {
            return false;
        }

        if (array_key_exists('enabled', $input) && $input['enabled'] === false) {
            return false;
        }

        /** @var array<string, mixed>|null $status */
        $status = $input['status'] ?? null;
        if (! is_array($status)) {
            return false;
        }

        /** @var array<string, mixed>|null $current */
        $current = $status['current'] ?? null;
        $state = is_array($current) && isset($current['state'])
            ? strtolower((string) $current['state'])
            : strtolower((string) ($status['state'] ?? ''));

        return in_array($state, ['connected', 'live', 'streaming'], true);
    }

    public function iframeSrc(): ?string
    {
        $url = config('streaming.cloudflare.playback_url');
        if (is_string($url) && $url !== '') {
            return $url;
        }

        $videoUid = config('streaming.cloudflare.playback_video_uid');
        if (is_string($videoUid) && $videoUid !== '') {
            return sprintf('https://iframe.videodelivery.net/%s', $videoUid);
        }

        $liveInput = $this->getLiveInput();
        if (is_array($liveInput)) {
            return $this->playbackIframeFromLiveInput($liveInput);
        }

        $uid = $this->resolveLiveInputUid();
        if ($uid !== null) {
            return sprintf('https://iframe.videodelivery.net/%s', $uid);
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getLiveInput(?string $uid = null): ?array
    {
        $uid ??= $this->resolveLiveInputUid();
        if ($uid === null) {
            return null;
        }

        $response = $this->request('get', sprintf('stream/live_inputs/%s', $uid));

        if (! $response->successful()) {
            return null;
        }

        /** @var array<string, mixed>|null */
        return $response->json('result');
    }

    /**
     * @return array<string, mixed>
     */
    public function createLiveInput(string $name = 'Live Stream'): array
    {
        $response = $this->request('post', 'stream/live_inputs', [
            'meta' => ['name' => $name],
            'recording' => ['mode' => 'automatic'],
            'enabled' => true,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException($this->errorMessage($response, 'Impossible de créer l’entrée live Cloudflare.'));
        }

        /** @var array<string, mixed> $result */
        $result = $response->json('result') ?? [];

        if (isset($result['uid']) && is_string($result['uid'])) {
            Setting::set(self::SETTING_LIVE_INPUT_UID, $result['uid']);
        }

        return $result;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function setLiveInputEnabled(bool $enabled, ?string $uid = null): ?array
    {
        $uid ??= $this->resolveLiveInputUid();
        if ($uid === null) {
            return null;
        }

        $response = $this->request('put', sprintf('stream/live_inputs/%s', $uid), [
            'enabled' => $enabled,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException($this->errorMessage($response, $enabled
                ? 'Impossible d’activer l’entrée live.'
                : 'Impossible de désactiver l’entrée live.'));
        }

        return $this->normalizeLiveInputResult($response->json('result'), $uid);
    }

    /**
     * @return array{rtmps_url: ?string, stream_key: ?string, srt_url: ?string, playback: ?string, enabled: ?bool, status: ?string, uid: ?string}
     */
    public function getIngestInfo(?string $uid = null): array
    {
        $input = $this->getLiveInput($uid);
        if ($input === null) {
            return [
                'rtmps_url' => null,
                'stream_key' => null,
                'srt_url' => null,
                'playback' => $this->playbackIframeFromLiveInput($input) ?? $this->iframeSrc(),
                'enabled' => null,
                'status' => null,
                'uid' => $uid,
            ];
        }

        /** @var array<string, mixed>|null $rtmps */
        $rtmps = $input['rtmps'] ?? null;
        /** @var array<string, mixed>|null $srt */
        $srt = $input['srt'] ?? null;
        /** @var array<string, mixed>|null $status */
        $status = $input['status'] ?? null;

        return [
            'rtmps_url' => is_array($rtmps) ? ($rtmps['url'] ?? null) : null,
            'stream_key' => is_array($rtmps) ? ($rtmps['streamKey'] ?? null) : null,
            'srt_url' => is_array($srt) ? ($srt['url'] ?? null) : null,
            'playback' => $this->playbackIframeFromLiveInput($input) ?? $this->iframeSrc(),
            'enabled' => isset($input['enabled']) ? (bool) $input['enabled'] : true,
            'status' => is_array($status) ? ($status['current']['state'] ?? $status['state'] ?? null) : null,
            'uid' => isset($input['uid']) ? (string) $input['uid'] : null,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function fetchLiveInputStatus(): ?array
    {
        $uid = $this->resolveLiveInputUid();
        if ($uid === null) {
            return null;
        }

        $response = $this->request('get', sprintf('stream/live_inputs/%s', $uid));

        if (! $response->successful()) {
            Log::warning('cloudflare.live_input.fetch_failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        }

        return $response->json();
    }

    /**
     * @param  array<string, mixed>  $body
     */
    protected function request(string $method, string $path, array $body = []): Response
    {
        $accountId = $this->accountId();
        $token = $this->apiToken();

        if ($accountId === null || $token === null) {
            throw new RuntimeException('Cloudflare Stream n’est pas configuré (CLOUDFLARE_ACCOUNT_ID / CLOUDFLARE_STREAM_API_TOKEN).');
        }

        $url = sprintf(
            'https://api.cloudflare.com/client/v4/accounts/%s/%s',
            $accountId,
            ltrim($path, '/'),
        );

        $pending = Http::withToken($token)->acceptJson()->timeout(30);

        return match (strtolower($method)) {
            'post' => $pending->post($url, $body),
            'put' => $pending->put($url, $body),
            'delete' => $pending->delete($url),
            default => $pending->get($url),
        };
    }

    protected function accountId(): ?string
    {
        $id = config('streaming.cloudflare.account_id');

        return is_string($id) && $id !== '' ? $id : null;
    }

    protected function apiToken(): ?string
    {
        $token = config('streaming.cloudflare.api_token');

        return is_string($token) && $token !== '' ? $token : null;
    }

    protected function errorMessage(Response $response, string $fallback): string
    {
        $errors = $response->json('errors');
        if (is_array($errors) && isset($errors[0]['message'])) {
            return (string) $errors[0]['message'];
        }

        return $fallback;
    }

    /**
     * Cloudflare renvoie parfois `result` comme chaîne (uid) au lieu d’un objet.
     *
     * @return array<string, mixed>|null
     */
    protected function normalizeLiveInputResult(mixed $result, string $uid): ?array
    {
        if (is_array($result)) {
            return $result;
        }

        if (is_string($result) && $result !== '') {
            return $this->getLiveInput($result) ?? ['uid' => $result];
        }

        return $this->getLiveInput($uid);
    }

    /**
     * @param  array<string, mixed>  $input
     */
    protected function playbackIframeFromLiveInput(array $input): ?string
    {
        /** @var array<string, mixed>|null $webRtcPlayback */
        $webRtcPlayback = $input['webRTCPlayback'] ?? null;
        if (is_array($webRtcPlayback) && isset($webRtcPlayback['url']) && is_string($webRtcPlayback['url'])) {
            $iframeFromWebRtc = preg_replace('#/webRTC/play$#', '/iframe', $webRtcPlayback['url']);
            if (is_string($iframeFromWebRtc) && $iframeFromWebRtc !== $webRtcPlayback['url']) {
                return $iframeFromWebRtc;
            }
        }

        $uid = $input['uid'] ?? null;
        if (is_string($uid) && $uid !== '') {
            return sprintf('https://iframe.videodelivery.net/%s', $uid);
        }

        return null;
    }
}
