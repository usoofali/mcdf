<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'description',
        'group',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'string',
        ];
    }

    public static function get(string $key, $default = null)
    {
        $setting = static::where('key', $key)->first();

        if (!$setting) {
            return $default;
        }

        return match ($setting->type) {
            'integer' => (int) $setting->value,
            'decimal' => (float) $setting->value,
            'boolean' => (bool) $setting->value,
            'json' => json_decode($setting->value, true),
            default => $setting->value,
        };
    }

    public static function set(string $key, $value, string $type = 'string', ?string $description = null, string $group = 'general'): Setting
    {
        $setting = static::firstOrNew(['key' => $key]);
        $setting->value = is_array($value) ? json_encode($value) : (string) $value;
        $setting->type = $type;
        $setting->description = $description ?? $setting->description;
        $setting->group = $group;
        $setting->save();

        return $setting;
    }

    protected static function boot(): void
    {
        parent::boot();

        static::created(function (Setting $setting): void {
            session()->flash('success', __('Setting created successfully.'));
        });

        static::updated(function (Setting $setting): void {
            session()->flash('success', __('Setting updated successfully.'));
        });

        static::deleted(function (Setting $setting): void {
            session()->flash('success', __('Setting deleted successfully.'));
        });
    }
}
