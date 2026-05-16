<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    /**
     * Valeur en base : aws_ivs, cloudflare ou youtube (prioritaire sur STREAMING_PROVIDER du .env).
     */
    public const STREAMING_PROVIDER = 'streaming_provider';

    /** '1' si la page publique /live doit afficher le lecteur quand le flux est actif. */
    public const LIVE_PUBLIC_VISIBLE = 'live_public_visible';

    /**
     * Valeur saisie en admin : ID ou URL YouTube (prioritaire sur YOUTUBE_LIVE_VIDEO_ID du .env).
     */
    public const YOUTUBE_LIVE_VIDEO_ID = 'youtube_live_video_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $primaryKey = 'key';

    protected $fillable = [
        'key',
        'value',
    ];

    public static function get(string $key, ?string $default = null): ?string
    {
        $row = static::query()->find($key);

        return $row?->value ?? $default;
    }

    public static function set(string $key, ?string $value): void
    {
        static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value],
        );
    }

    public static function isLivePublicVisible(): bool
    {
        return static::get(self::LIVE_PUBLIC_VISIBLE) === '1';
    }

    public static function setLivePublicVisible(bool $visible): void
    {
        static::set(self::LIVE_PUBLIC_VISIBLE, $visible ? '1' : '0');
    }
}
