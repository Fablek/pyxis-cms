<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    private static string $cachePrefix = 'setting_';

    protected static function booted(): void
    {
        static::saved(function (Setting $setting) {
            Cache::forget(self::$cachePrefix . $setting->key);
        });

        static::deleted(function (Setting $setting) {
            Cache::forget(self::$cachePrefix . $setting->key);
        });
    }

    public static function get(string $key, $default = null) 
    {
        return Cache::rememberForever(self::$cachePrefix . $key, function () use ($key, $default) {
            return static::where('key', $key)->first()?->value ?? $default;
        });
    }

    public static function set(string $key, $value)
    {
        $setting = static::updateOrCreate(['key' => $key], ['value' => $value]);

        Cache::forget(self::$cachePrefix . $key);

        return $setting;
    }
}
