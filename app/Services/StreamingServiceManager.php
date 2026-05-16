<?php

namespace App\Services;

use App\Contracts\StreamingServiceInterface;
use App\Models\Setting;
use InvalidArgumentException;

class StreamingServiceManager
{
    /** @var list<string> */
    public const PROVIDERS = ['aws_ivs', 'cloudflare', 'youtube'];

    public function resolvedProvider(): string
    {
        $stored = Setting::get(Setting::STREAMING_PROVIDER);
        if ($stored !== null && $stored !== '' && $this->isAllowedProvider($stored)) {
            return $stored;
        }

        return (string) config('streaming.default', 'aws_ivs');
    }

    /**
     * Une valeur est définie explicitement en admin (prioritaire sur le .env).
     */
    public function isProviderForcedFromDatabase(): bool
    {
        $stored = Setting::get(Setting::STREAMING_PROVIDER);

        return $stored !== null && $stored !== '' && $this->isAllowedProvider($stored);
    }

    public function isAllowedProvider(string $provider): bool
    {
        return in_array($provider, self::PROVIDERS, true);
    }

    public function driver(?string $provider = null): StreamingServiceInterface
    {
        $provider ??= $this->resolvedProvider();

        if (! $this->isAllowedProvider($provider)) {
            throw new InvalidArgumentException("Fournisseur streaming inconnu : {$provider}");
        }

        return match ($provider) {
            'aws_ivs' => app(AwsIvsStreamService::class),
            'cloudflare' => app(CloudflareStreamService::class),
            'youtube' => app(YoutubeEmbedStreamService::class),
        };
    }

    public function default(): StreamingServiceInterface
    {
        return $this->driver();
    }
}
