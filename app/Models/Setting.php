<?php

namespace App\Models;

use Database\Factories\SettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Deliberately uncached: the settings table has a handful of rows, so a plain
 * query per read is cheap — and it avoids the correctness pitfalls of any
 * process-lifetime cache (cache-store serialization quirks, or a static
 * property that would leak stale values across requests/tests). Callers that
 * read several keys in one render (the navbar/footer) should fetch once via
 * `Setting::query()->pluck('value', 'key')` instead of calling get() repeatedly.
 */
#[Fillable(['key', 'value', 'group'])]
class Setting extends Model
{
    /** @use HasFactory<SettingFactory> */
    use HasFactory;

    public static function get(string $key, ?string $default = null): ?string
    {
        return static::query()->where('key', $key)->value('value') ?? $default;
    }

    public static function set(string $key, ?string $value, string $group = 'general'): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value, 'group' => $group]);
    }
}
