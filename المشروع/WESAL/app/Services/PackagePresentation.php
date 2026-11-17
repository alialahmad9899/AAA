<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

/**
 * Presentation-only premium package fields stored in the existing settings table.
 * Price, validity and entitlements remain authoritative in the packages table.
 * No database migration is required.
 */
class PackagePresentation
{
    public const SETTING_KEY = 'premium_package_presentation';

    private const BADGE_STYLES = ['silver', 'gold', 'teal'];

    private const ICON_OPTIONS = [
        'check' => 'علامة تحقق',
        'star' => 'نجمة',
        'workspace_premium' => 'عضوية مميزة',
        'verified' => 'موثّق',
        'favorite' => 'قلب',
        'chat' => 'محادثة',
        'visibility' => 'مشاهدة',
        'photo' => 'صورة',
        'photo_library' => 'معرض صور',
        'handshake' => 'توافق',
        'shield' => 'حماية',
        'bolt' => 'سرعة',
        'people' => 'أشخاص',
        'infinity' => 'غير محدود',
    ];

    public static function badgeStyles(): array
    {
        return [
            'silver' => 'فضي',
            'gold' => 'ذهبي',
            'teal' => 'بترولي',
        ];
    }

    public static function iconOptions(): array
    {
        return self::ICON_OPTIONS;
    }

    public static function normaliseBadgeStyle($value): string
    {
        $style = strtolower(trim((string) $value));
        return in_array($style, self::BADGE_STYLES, true) ? $style : 'teal';
    }

    /**
     * Convert legacy plain strings and bilingual feature rows to the API shape.
     * This method is intentionally pure so it can be tested without a database.
     */
    public static function normaliseFeatureItems($values, string $language): array
    {
        if (is_string($values)) {
            $values = preg_split('/\\R/u', $values) ?: [];
        }
        if (!is_array($values)) {
            return [];
        }

        $items = [];
        foreach (array_slice($values, 0, 30) as $value) {
            if (is_array($value)) {
                $title = trim((string) ($value['title'] ?? ''));
                if ($title === '') {
                    $title = trim((string) (
                        $value['title_' . $language] ?? $value[$language] ?? ''
                    ));
                }
                if ($title === '') {
                    $otherLanguage = $language === 'en' ? 'ar' : 'en';
                    $title = trim((string) ($value['title_' . $otherLanguage] ?? ''));
                }
                $iconKey = (string) ($value['icon_key'] ?? 'check');
            } else {
                $title = trim((string) $value);
                $iconKey = 'check';
            }

            if ($title === '') {
                continue;
            }
            if (!array_key_exists($iconKey, self::ICON_OPTIONS)) {
                $iconKey = 'check';
            }

            $items[] = [
                'title' => $title,
                'icon_key' => $iconKey,
            ];
        }

        return array_values($items);
    }

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
        // New editor submits shared bilingual rows. Old installations may still
        // contain separate text arrays or feature_items_ar/en objects.
        $hasSharedFeatures = array_key_exists('features', $values);
        $arSource = $hasSharedFeatures
            ? $values['features']
            : ($values['feature_items_ar'] ?? $values['features_ar'] ?? []);
        $enSource = $hasSharedFeatures
            ? $values['features']
            : ($values['feature_items_en'] ?? $values['features_en'] ?? []);

        $featureItemsAr = self::normaliseFeatureItems($arSource, 'ar');
        $featureItemsEn = self::normaliseFeatureItems($enSource, 'en');
        if ($featureItemsEn === [] && $featureItemsAr !== []) {
            $featureItemsEn = $featureItemsAr;
        }
        if ($featureItemsAr === [] && $featureItemsEn !== []) {
            $featureItemsAr = $featureItemsEn;
        }

        $badgeIcon = (string) ($values['badge_icon_key'] ?? 'workspace_premium');
        if (!array_key_exists($badgeIcon, self::ICON_OPTIONS)) {
            $badgeIcon = 'workspace_premium';
        }

        return [
            'package_id' => $packageId,
            'name_en' => trim((string) ($values['name_en'] ?? '')),
            'description_ar' => trim((string) ($values['description_ar'] ?? '')),
            'description_en' => trim((string) ($values['description_en'] ?? '')),
            // Legacy strings are retained for older mobile builds/resources.
            'features_ar' => array_column($featureItemsAr, 'title'),
            'features_en' => array_column($featureItemsEn, 'title'),
            'feature_items_ar' => $featureItemsAr,
            'feature_items_en' => $featureItemsEn,
            'badge_text_ar' => trim((string) ($values['badge_text_ar'] ?? '')),
            'badge_text_en' => trim((string) ($values['badge_text_en'] ?? '')),
            'badge_icon_key' => $badgeIcon,
            'badge_style' => self::normaliseBadgeStyle($values['badge_style'] ?? 'teal'),
            'is_featured' => filter_var($values['is_featured'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'duration_label_ar' => trim((string) ($values['duration_label_ar'] ?? '')),
            'duration_label_en' => trim((string) ($values['duration_label_en'] ?? '')),
            'archived' => filter_var($values['archived'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'sort_order' => max(0, min(9999, (int) ($values['sort_order'] ?? ($packageId * 10)))),
        ];
    }

    private static function persist(array $stored): void
    {
        $payload = json_encode(
            array_values($stored),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
        );
        if (!is_string($payload)) {
            throw new \RuntimeException('Unable to encode premium package presentation.');
        }

        $setting = Setting::where('type', self::SETTING_KEY)->first();
        if (!$setting) {
            $setting = new Setting();
            $setting->type = self::SETTING_KEY;
        }

        $setting->value = $payload;
        if ($setting->save() !== true) {
            throw new \RuntimeException('Premium package presentation was not saved.');
        }

        Cache::forget('settings');
        $storedValue = Setting::where('type', self::SETTING_KEY)->value('value');
        if (!is_string($storedValue)
            || !hash_equals(hash('sha256', $payload), hash('sha256', $storedValue))) {
            Cache::forget('settings');
            throw new \RuntimeException('Premium package presentation read-back verification failed.');
        }
    }
}
