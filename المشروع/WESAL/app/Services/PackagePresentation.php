<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

/**
 * Presentation-only package fields stored in the existing settings table.
 * No new columns or migration are required on hosted installations.
 */
class PackagePresentation
{
    public const SETTING_KEY = 'premium_package_presentation';

    public static function all(): array
    {
        $raw = function_exists('get_setting') ? get_setting(self::SETTING_KEY) : null;
        $stored = is_array($raw) ? $raw : json_decode((string) $raw, true);
        if (!is_array($stored)) {
            return [];
        }

        $result = [];
        foreach ($stored as $key => $value) {
            if (!is_array($value)) {
                continue;
            }
            $packageId = (int) ($value['package_id'] ?? $key);
            if ($packageId > 0) {
                $result[(string) $packageId] = self::normalise($value, $packageId);
            }
        }

        return $result;
    }

    public static function get(int $packageId): array
    {
        $stored = self::all();
        return $stored[(string) $packageId] ?? self::normalise([], $packageId);
    }

    public static function save(int $packageId, array $values): void
    {
        if ($packageId <= 0) {
            throw new \InvalidArgumentException('A valid package ID is required.');
        }

        $stored = self::all();
        $stored[(string) $packageId] = self::normalise($values, $packageId);
        self::persist($stored);
    }

    public static function forget(int $packageId): void
    {
        $stored = self::all();
        unset($stored[(string) $packageId]);
        self::persist($stored);
    }

    private static function normalise(array $values, int $packageId): array
    {
        return [
            'package_id' => $packageId,
            'name_en' => trim((string) ($values['name_en'] ?? '')),
            'description_ar' => trim((string) ($values['description_ar'] ?? '')),
            'description_en' => trim((string) ($values['description_en'] ?? '')),
            'features_ar' => self::normaliseFeatures($values['features_ar'] ?? []),
            'features_en' => self::normaliseFeatures($values['features_en'] ?? []),
            'sort_order' => max(0, min(9999, (int) ($values['sort_order'] ?? ($packageId * 10)))),
        ];
    }

    private static function normaliseFeatures($values): array
    {
        if (is_string($values)) {
            $values = preg_split('/\\R/u', $values) ?: [];
        }
        if (!is_array($values)) {
            return [];
        }

        $features = [];
        foreach ($values as $value) {
            $line = trim((string) $value);
            if ($line !== '') {
                $features[] = $line;
            }
            if (count($features) >= 30) {
                break;
            }
        }

        return array_values($features);
    }

    private static function persist(array $stored): void
    {
        $setting = Setting::where('type', self::SETTING_KEY)->first();
        if (!$setting) {
            $setting = new Setting();
            $setting->type = self::SETTING_KEY;
        }

        $setting->value = json_encode(
            array_values($stored),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
        );
        $setting->save();
        Cache::forget('settings');
    }
}
