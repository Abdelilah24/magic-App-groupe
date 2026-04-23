<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class AppSetting extends Model
{
    protected $fillable = ['key', 'value'];

    // ─── Helpers statiques ───────────────────────────────────────────────────

    /**
     * Lire un paramètre (avec cache 1h).
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember("app_setting_{$key}", 3600, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();
            return $setting ? $setting->value : $default;
        });
    }

    /**
     * Écrire ou mettre à jour un paramètre (invalide le cache).
     */
    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget("app_setting_{$key}");
    }

    /**
     * Supprimer un paramètre.
     */
    public static function remove(string $key): void
    {
        static::where('key', $key)->delete();
        Cache::forget("app_setting_{$key}");
    }

    // ─── Clés connues ────────────────────────────────────────────────────────

    const KEY_ADMIN_EMAIL = 'admin_email';
    const KEY_APP_LOGO    = 'app_logo';
}
