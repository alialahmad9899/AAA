<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class VipServiceCatalog
{
    private const SETTING_KEY = 'vip_services_catalog';

    /**
     * الأنواع الثلاثة التي يدعمها تطبيق الموبايل حالياً.
     * يمكن تعديل الاسم والوصف والسعر وحالة التفعيل دون تغيير Flutter.
     */
    public static function defaults(): array
    {
        return [
            [
                'id' => 1,
                'service_type' => 'highlight_profile',
                'title' => 'خدمة نشر طلب زواج مميّز – منصة وصال',
                'title_ar' => 'خدمة نشر طلب زواج مميّز – منصة وصال',
                'title_en' => 'Featured Marriage Proposal – Wesal',
                'price' => 10.00,
                'currency' => '$',
                'description' => 'منشور تفصيلي وفيديو مخصص على فيسبوك وتيك توك مع نشر رابط البروفايل للتواصل الآمن داخل التطبيق وبدون نشر رقم الهاتف.',
                'promo_ar' => 'منشور تفصيلي وفيديو مخصص على فيسبوك وتيك توك مع نشر رابط البروفايل للتواصل الآمن داخل التطبيق وبدون نشر رقم الهاتف.',
                'promo_en' => 'A featured marriage proposal with custom media and a safe in-app profile link.',
                'icon' => static_asset('assets/img/vip/highlight.png'),
                'active' => true,
            ],
            [
                'id' => 2,
                'service_type' => 'direct_contact',
                'title' => 'خدمة التواصل المباشر خارج المنصة – منصة وصال',
                'title_ar' => 'خدمة التواصل المباشر خارج المنصة – منصة وصال',
                'title_en' => 'Direct Contact Service – Wesal',
                'price' => 25.00,
                'currency' => '$',
                'description' => 'منشور تفصيلي وفيديو على فيسبوك وتيك توك مع إتاحة وسيلة التواصل المباشر الخارجي وفق آلية التنسيق مع مراعاة الخصوصية.',
                'promo_ar' => 'منشور تفصيلي وفيديو على فيسبوك وتيك توك مع إتاحة وسيلة التواصل المباشر الخارجي وفق آلية التنسيق مع مراعاة الخصوصية.',
                'promo_en' => 'A featured post and video with an optional coordinated external contact method.',
                'icon' => static_asset('assets/img/vip/social.png'),
                'active' => true,
            ],
            [
                'id' => 3,
                'service_type' => 'special_matchmaking',
                'title' => 'خدمة البحث الخاص عن شريك الحياة – منصة وصال',
                'title_ar' => 'خدمة البحث الخاص عن شريك الحياة – منصة وصال',
                'title_en' => 'Personal Matchmaking Search – Wesal',
                'price' => 100.00,
                'currency' => '$',
                'description' => 'بحث مخصص بواسطة فريق منصة وصال في قاعدة البيانات الممتدة والربط المباشر والآمن داخل التطبيق بخصوصية تامة بدون نشر علني.',
                'promo_ar' => 'بحث مخصص بواسطة فريق منصة وصال في قاعدة البيانات الممتدة والربط المباشر والآمن داخل التطبيق بخصوصية تامة بدون نشر علني.',
                'promo_en' => 'A private, personalized partner search by the Wesal team with confidential coordination.',
                'icon' => static_asset('assets/img/vip/matchmaking.png'),
                'active' => true,
            ],
        ];
    }

    /**
     * تحميل إعدادات الإدارة من جدول settings ودمجها مع أنواع الخدمة المعتمدة.
     */
    public static function all(): array
    {
        $storedValue = function_exists('get_setting')
            ? get_setting(self::SETTING_KEY)
            : null;

        $stored = is_array($storedValue)
            ? $storedValue
            : json_decode((string) $storedValue, true);

        if (!is_array($stored)) {
            return self::defaults();
        }

        $byType = [];
        foreach ($stored as $item) {
            if (is_array($item) && !empty($item['service_type'])) {
                $byType[$item['service_type']] = $item;
            }
        }

        $services = [];
        foreach (self::defaults() as $default) {
            $service = array_merge($default, $byType[$default['service_type']] ?? []);
            $service['active'] = filter_var($service['active'] ?? true, FILTER_VALIDATE_BOOLEAN);
            $service['price'] = (float) $service['price'];
            $services[] = $service;
        }

        return $services;
    }

    public static function active(): array
    {
        return array_values(array_filter(self::all(), function ($service) {
            return !empty($service['active']);
        }));
    }

    public static function findActive(string $serviceType): ?array
    {
        foreach (self::active() as $service) {
            if ($service['service_type'] === $serviceType) {
                return $service;
            }
        }

        return null;
    }

    /**
     * الحفظ في جدول settings الموجود مسبقاً مع إبطال Cache الإعدادات.
     */
    public static function save(array $services): void
    {
        $setting = Setting::where('type', self::SETTING_KEY)->first();

        if (!$setting) {
            $setting = new Setting();
            $setting->type = self::SETTING_KEY;
        }

        $setting->value = json_encode(
            array_values($services),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
        );
        $setting->save();

        Cache::forget('settings');
    }
}
