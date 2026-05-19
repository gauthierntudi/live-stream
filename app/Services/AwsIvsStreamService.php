<?php

namespace App\Services;

use App\Contracts\StreamingServiceInterface;
use App\Models\Setting;
use Aws\Exception\AwsException;
use Aws\IVS\IVSClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class AwsIvsStreamService implements StreamingServiceInterface
{
    public const SETTING_CHANNEL_ARN = 'aws_ivs_channel_arn';

    /** Cache lecture / ingest si GetChannel échoue temporairement (évite lecteur “non configuré” et champs OBS vides). */
    public const SETTING_PLAYBACK_URL = 'aws_ivs_playback_url';

    public const SETTING_INGEST_ENDPOINT = 'aws_ivs_ingest_endpoint';

    public function providerKey(): string
    {
        return 'aws_ivs';
    }

    public function providerLabel(): string
    {
        return 'AWS IVS';
    }

    public function playbackMode(): string
    {
        return 'hls';
    }

    public function isConfigured(): bool
    {
        return $this->accessKeyId() !== null
            && $this->secretAccessKey() !== null
            && $this->region() !== null;
    }

    public function resolveLiveInputUid(): ?string
    {
        $stored = Setting::get(self::SETTING_CHANNEL_ARN);
        if (is_string($stored) && $stored !== '') {
            return $stored;
        }

        $fromEnv = config('streaming.aws_ivs.channel_arn');

        return is_string($fromEnv) && $fromEnv !== '' ? $fromEnv : null;
    }

    public function iframeSrc(): ?string
    {
        return null;
    }

    public function playbackUrl(): ?string
    {
        $url = config('streaming.aws_ivs.playback_url');
        if (is_string($url) && $url !== '') {
            return $url;
        }

        $channel = $this->getLiveInput();
        if ($channel !== null && isset($channel['playbackUrl']) && is_string($channel['playbackUrl']) && $channel['playbackUrl'] !== '') {
            return $channel['playbackUrl'];
        }

        $cached = Setting::get(self::SETTING_PLAYBACK_URL);
        if ($this->resolveLiveInputUid() !== null && is_string($cached) && $cached !== '') {
            return $cached;
        }

        return null;
    }

    public function isBroadcasting(): bool
    {
        $status = strtoupper((string) ($this->getIngestInfo()['status'] ?? ''));

        return in_array($status, ['LIVE', 'LIVE_ONLY'], true);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getLiveInput(?string $uid = null): ?array
    {
        $arn = $uid ?? $this->resolveLiveInputUid();
        if ($arn === null) {
            return null;
        }

        try {
            $result = $this->client()->getChannel(['arn' => $arn]);
            $channel = $result['channel'] ?? null;
            $this->persistChannelEndpointCache($channel);

            return $channel;
        } catch (AwsException $e) {
            Log::warning('aws_ivs.channel.fetch_failed', [
                'arn' => $arn,
                'message' => $e->getAwsErrorMessage(),
            ]);

            return null;
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function createLiveInput(string $name = 'Live Stream'): array
    {
        $name = $this->sanitizeIvsChannelName($name);
        $cleanupArn = null;

        try {
            $channelResult = $this->client()->createChannel([
                'name' => $name,
                'latencyMode' => 'LOW',
                'type' => 'STANDARD',
                'authorized' => false,
            ]);

            /** @var array<string, mixed> $channel */
            $channel = $channelResult['channel'] ?? [];
            $arn = $channel['arn'] ?? null;

            if (! is_string($arn) || $arn === '') {
                throw new RuntimeException('AWS IVS n’a pas renvoyé d’ARN de canal.');
            }

            $cleanupArn = $arn;

            $streamKeyPayload = $this->ensureStreamKeyPayloadForChannel($arn);

            if ($streamKeyPayload === null) {
                throw new RuntimeException(
                    'AWS IVS n’a pas permis d’obtenir une stream key pour ce canal (quota ou limite atteinte). '
                    .'Vérifiez dans la console AWS IVS les clés existantes ou demandez une augmentation de quota.',
                );
            }

            Setting::set(self::SETTING_CHANNEL_ARN, $arn);
            $this->persistChannelEndpointCache($channel);

            return [
                'channel' => $channel,
                'streamKey' => $streamKeyPayload,
            ];
        } catch (AwsException $e) {
            if ($cleanupArn !== null) {
                $this->tryDeleteChannel($cleanupArn);
            }

            throw new RuntimeException($this->errorMessage($e, 'Impossible de créer le canal AWS IVS.'));
        } catch (RuntimeException $e) {
            if ($cleanupArn !== null) {
                $this->tryDeleteChannel($cleanupArn);
            }

            throw $e;
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    public function setLiveInputEnabled(bool $enabled, ?string $uid = null): ?array
    {
        $arn = $uid ?? $this->resolveLiveInputUid();
        if ($arn === null) {
            return null;
        }

        if ($enabled) {
            return $this->getLiveInput($arn);
        }

        try {
            $streams = $this->client()->listStreams([
                'channelArn' => $arn,
            ]);

            foreach ($streams['streams'] ?? [] as $stream) {
                if (! is_array($stream) || empty($stream['streamId'])) {
                    continue;
                }

                $this->client()->stopStream([
                    'channelArn' => $arn,
                    'streamId' => (string) $stream['streamId'],
                ]);
            }

            return $this->getLiveInput($arn);
        } catch (AwsException $e) {
            throw new RuntimeException($this->errorMessage($e, 'Impossible d’arrêter le stream AWS IVS.'));
        }
    }

    /**
     * @return array{rtmps_url: ?string, stream_key: ?string, srt_url: ?string, playback: ?string, enabled: ?bool, status: ?string, uid: ?string}
     */
    public function getIngestInfo(?string $uid = null): array
    {
        $arn = $uid ?? $this->resolveLiveInputUid();

        if ($arn === null) {
            return [
                'rtmps_url' => null,
                'stream_key' => null,
                'srt_url' => null,
                'playback' => $this->playbackUrl(),
                'enabled' => null,
                'status' => null,
                'uid' => null,
            ];
        }

        $channel = $this->getLiveInput($arn);

        if ($channel === null) {
            $ingestEndpoint = Setting::get(self::SETTING_INGEST_ENDPOINT);
            $ingestEndpoint = is_string($ingestEndpoint) ? trim($ingestEndpoint) : '';
            $rtmpsUrl = $ingestEndpoint !== ''
                ? sprintf('rtmps://%s:443/app/', $ingestEndpoint)
                : null;
            $streamKeyValue = $this->resolveStreamKeyValue($arn);
            $status = $this->resolveStreamStatus($arn);

            return [
                'rtmps_url' => $rtmpsUrl,
                'stream_key' => $streamKeyValue,
                'srt_url' => null,
                'playback' => $this->playbackUrl(),
                'enabled' => $rtmpsUrl !== null && $streamKeyValue !== null,
                'status' => $status,
                'uid' => $arn,
            ];
        }

        $ingestEndpoint = isset($channel['ingestEndpoint']) ? (string) $channel['ingestEndpoint'] : '';
        $rtmpsUrl = $ingestEndpoint !== ''
            ? sprintf('rtmps://%s:443/app/', $ingestEndpoint)
            : null;

        $streamKeyValue = $this->resolveStreamKeyValue($arn);
        $status = $this->resolveStreamStatus($arn);

        $playback = isset($channel['playbackUrl']) && is_string($channel['playbackUrl']) && $channel['playbackUrl'] !== ''
            ? $channel['playbackUrl']
            : $this->playbackUrl();

        return [
            'rtmps_url' => $rtmpsUrl,
            'stream_key' => $streamKeyValue,
            'srt_url' => null,
            'playback' => $playback,
            'enabled' => $rtmpsUrl !== null && $streamKeyValue !== null,
            'status' => $status,
            'uid' => $arn,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function fetchLiveInputStatus(): ?array
    {
        $arn = $this->resolveLiveInputUid();
        if ($arn === null) {
            return null;
        }

        try {
            $channel = $this->getLiveInput($arn);
            $streams = $this->client()->listStreams(['channelArn' => $arn]);

            return [
                'channel' => $channel,
                'streams' => $streams['streams'] ?? [],
            ];
        } catch (AwsException $e) {
            Log::warning('aws_ivs.status.fetch_failed', [
                'message' => $e->getAwsErrorMessage(),
            ]);

            return null;
        }
    }

    protected function resolveStreamKeyValue(?string $channelArn): ?string
    {
        if ($channelArn === null) {
            return null;
        }

        $payload = $this->fetchFirstStreamKeyPayload($channelArn);

        if ($payload !== null && isset($payload['value'])) {
            return (string) $payload['value'];
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null Données streamKey renvoyées par IVS (dont value si présente).
     */
    protected function fetchFirstStreamKeyPayload(string $channelArn): ?array
    {
        try {
            $keys = $this->client()->listStreamKeys(['channelArn' => $channelArn]);

            foreach ($keys['streamKeys'] ?? [] as $key) {
                if (! is_array($key) || empty($key['arn'])) {
                    continue;
                }

                $detail = $this->client()->getStreamKey(['arn' => (string) $key['arn']]);

                if (isset($detail['streamKey']) && is_array($detail['streamKey'])) {
                    return $detail['streamKey'];
                }
            }
        } catch (AwsException $e) {
            Log::warning('aws_ivs.stream_key.fetch_failed', [
                'channelArn' => $channelArn,
                'message' => $e->getAwsErrorMessage(),
            ]);
        }

        return null;
    }

    /**
     * Crée une stream key ou réutilise la première existante (limite IVs souvent 1 clé / canal).
     *
     * @return array<string, mixed>|null
     */
    protected function ensureStreamKeyPayloadForChannel(string $channelArn): ?array
    {
        $existing = $this->fetchFirstStreamKeyPayload($channelArn);
        if ($existing !== null) {
            return $existing;
        }

        try {
            $keyResult = $this->client()->createStreamKey([
                'channelArn' => $channelArn,
            ]);

            return isset($keyResult['streamKey']) && is_array($keyResult['streamKey'])
                ? $keyResult['streamKey']
                : null;
        } catch (AwsException $e) {
            if ($this->isStreamKeyQuotaExceededException($e)) {
                $after = $this->fetchFirstStreamKeyPayload($channelArn);
                if ($after !== null) {
                    Log::notice('aws_ivs.stream_key.reused_after_quota_error', [
                        'channelArn' => $channelArn,
                        'aws_message' => $e->getAwsErrorMessage(),
                    ]);

                    return $after;
                }
            }

            throw $e;
        }
    }

    protected function isStreamKeyQuotaExceededException(AwsException $e): bool
    {
        $code = (string) ($e->getAwsErrorCode() ?? '');
        $msg = strtolower($e->getAwsErrorMessage());

        return str_contains(strtolower($code), 'limit')
            || str_contains($msg, 'quota exceeded')
            || str_contains($msg, 'stream-key');
    }

    protected function tryDeleteChannel(string $channelArn): void
    {
        try {
            $this->client()->deleteChannel(['arn' => $channelArn]);
            $this->clearChannelEndpointCache();
        } catch (AwsException $e) {
            Log::warning('aws_ivs.channel.cleanup_delete_failed', [
                'channelArn' => $channelArn,
                'message' => $e->getAwsErrorMessage(),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>|null  $channel
     */
    protected function persistChannelEndpointCache(?array $channel): void
    {
        if ($channel === null) {
            return;
        }

        if (isset($channel['playbackUrl']) && is_string($channel['playbackUrl']) && $channel['playbackUrl'] !== '') {
            Setting::set(self::SETTING_PLAYBACK_URL, $channel['playbackUrl']);
        }

        if (isset($channel['ingestEndpoint']) && is_string($channel['ingestEndpoint']) && $channel['ingestEndpoint'] !== '') {
            Setting::set(self::SETTING_INGEST_ENDPOINT, $channel['ingestEndpoint']);
        }
    }

    protected function clearChannelEndpointCache(): void
    {
        Setting::query()->whereKey(self::SETTING_PLAYBACK_URL)->delete();
        Setting::query()->whereKey(self::SETTING_INGEST_ENDPOINT)->delete();
    }

    protected function resolveStreamStatus(?string $channelArn): ?string
    {
        if ($channelArn === null) {
            return null;
        }

        try {
            $streams = $this->client()->listStreams(['channelArn' => $channelArn]);
            $first = $streams['streams'][0] ?? null;

            if (is_array($first) && isset($first['state'])) {
                return (string) $first['state'];
            }
        } catch (AwsException $e) {
            Log::warning('aws_ivs.stream_status.fetch_failed', [
                'message' => $e->getAwsErrorMessage(),
            ]);
        }

        return 'OFFLINE';
    }

    protected function client(): IVSClient
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('AWS IVS n’est pas configuré (AWS_ACCESS_KEY_ID / AWS_SECRET_ACCESS_KEY / AWS_IVS_REGION).');
        }

        return new IVSClient([
            'version' => 'latest',
            'region' => $this->region(),
            'credentials' => [
                'key' => $this->accessKeyId(),
                'secret' => $this->secretAccessKey(),
            ],
        ]);
    }

    protected function accessKeyId(): ?string
    {
        $key = config('streaming.aws_ivs.access_key_id');

        return is_string($key) && $key !== '' ? $key : null;
    }

    protected function secretAccessKey(): ?string
    {
        $secret = config('streaming.aws_ivs.secret_access_key');

        return is_string($secret) && $secret !== '' ? $secret : null;
    }

    protected function region(): ?string
    {
        $region = config('streaming.aws_ivs.region');

        return is_string($region) && $region !== '' ? $region : null;
    }

    protected function errorMessage(AwsException $e, string $fallback): string
    {
        $message = $e->getAwsErrorMessage();

        return $message !== '' ? $message : $fallback;
    }

    /**
     * AWS IVS impose le motif ^[a-zA-Z0-9-_]*$ sur CreateChannel.name (pas d’espaces ni accents).
     */
    protected function sanitizeIvsChannelName(string $name): string
    {
        $ascii = trim(Str::ascii($name));
        $slug = Str::slug($ascii, '-', 'en');
        $slug = str_replace('_', '-', $slug);
        $clean = preg_replace('/[^a-zA-Z0-9_-]/', '', $slug) ?? '';
        $clean = trim($clean, '-_');

        if ($clean === '') {
            $clean = 'live-'.strtolower(bin2hex(random_bytes(4)));
        }

        return substr($clean, 0, 128);
    }
}
