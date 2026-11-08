<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class VipServiceCatalog
{
    private const SETTING_KEY = 'vip_services_catalog';

    public static function iconOptions(): array
    {
        return [
            'crown' => 'تاج VIP',
            'campaign' => 'ترويج وإعلان',
            'video' => 'فيديو',
            'search' => 'بحث',
            'user_search' => 'بحث عن شريك',
            'handshake' => 'توفيق وتواصل',
            'message' => 'مراسلة',
            'shield' => 'حماية وخصوصية',
            'gift' => 'هدية ومزايا',
            'link' => 'رابط',
            'support' => 'دعم ومساعدة',
            'star' => 'نجمة',
        ];
    }

    /**
     * Defaults preserve the original three app services, IDs, prices and features.
     */
    public static function defaults(): array
    {
        return [
            [
                'id' => 1,
                'service_type' => 'highlight_profile',
                'title' => 'خدمة نشر طلب زواج مميّز – منصة وصال',
                'title_ar' => 'خدمة نشر طلب زواج مميّز – منصة وصال',
                'title_en' => 'Featured Marriage Proposal Service – Wesal',
                'price' => 10.00,
                'currency' => '$',
                'description' => 'منشور تفصيلي وفيديو مخصص على فيسبوك وتيك توك مع نشر رابط البروفايل للتواصل الآمن داخل التطبيق وبدون نشر رقم الهاتف.',
                'promo_ar' => '✨ فرصة أكبر لوصول طلبك إلى الأشخاص المناسبين، بطريقة مميزة وآمنة مع منصة وصال.',
                'promo_en' => '✨ Higher reach for your proposal to suitable people in a safe and distinguished way.',
                'included_ar' => [
                    'منشور تفصيلي بمواصفاتك والشريك المطلوب على فيسبوك وتيك توك',
                    'إنتاج فيديو خاص ومميز يعرض طلبك بطريقة جذابة',
                    'نشر رابط بروفايلك للوصول المباشر والتواصل الآمن داخل التطبيق',
                    'الحفاظ على الخصوصية الكاملة وتأمين بياناتك الشخصية',
                ],
                'included_en' => [
                    'Detailed post on Wesal Facebook and TikTok pages',
                    'Custom video creation showcasing your proposal',
                    'Direct profile link for safe in-app messaging',
                    'Full privacy & profile protection',
                ],
                'icon' => static_asset('assets/img/vip/highlight.png'),
                'icon_key' => 'campaign',
                'sort_order' => 10,
                'allow_external_contact' => false,
                'show_special_thanks' => false,
                'active' => true,
                'archived' => false,
                'is_custom' => false,
            ],
            [
                'id' => 2,
                'service_type' => 'special_matchmaking',
                'title' => 'خدمة البحث الخاص عن شريك الحياة – منصة وصال',
                'title_ar' => 'خدمة البحث الخاص عن شريك الحياة – منصة وصال',
                'title_en' => 'Custom Partner Search Service – Wesal',
                'price' => 100.00,
                'currency' => '$',
                'description' => 'بحث مخصص بواسطة فريق منصة وصال في قاعدة البيانات الممتدة والربط المباشر والآمن داخل التطبيق بخصوصية تامة بدون نشر علني.',
                'promo_ar' => '✨ أنت تحدد توقعاتك، وفريق وصال يتولى مهمة البحث المخصص عن الشريك المناسب لك.',
                'promo_en' => '✨ You specify your expectations, and our team handles the personalized search for you!',
                'included_ar' => [
                    'بحث مخصص ويدوي نيابة عنك بواسطة فريق منصة وصال',
                    'البحث الشامل في قاعدة بيانات المشتركين والمهتمين بالزواج',
                    'ربط مباشر وآمن وتنسيق التواصل بين الطرفين داخل التطبيق',
                    'خصوصية تامة وشديدة طوال فترة البحث والدراسة',
                ],
                'included_en' => [
                    'Manual dedicated search by Wesal matchmaking team',
                    'Deep searching through entire registered user database',
                    'Direct safe connection and coordination inside the app',
                    '100% strict privacy protection throughout search',
                ],
                'icon' => static_asset('assets/img/vip/matchmaking.png'),
                'icon_key' => 'user_search',
                'sort_order' => 20,
                'allow_external_contact' => true,
                'show_special_thanks' => true,
                'active' => true,
                'archived' => false,
                'is_custom' => false,
            ],
            [
                'id' => 3,
                'service_type' => 'direct_contact',
                'title' => 'خدمة التواصل المباشر خارج المنصة – منصة وصال',
                'title_ar' => 'خدمة التواصل المباشر خارج المنصة – منصة وصال',
                'title_en' => 'Direct External Contact Service – Wesal',
                'price' => 25.00,
                'currency' => '$',
                'description' => 'منشور تفصيلي وفيديو على فيسبوك وتيك توك مع إتاحة وسيلة التواصل المباشر الخارجي وفق آلية التنسيق مع مراعاة الخصوصية.',
                'promo_ar' => '✨ وسّع فرصك في العثور على شريك الحياة المناسب، واجعل طلبك يصل للمزيد عبر الواتساب! ❤️',
                'promo_en' => '✨ Expand your chances of finding the right partner through direct external contact! ❤️',
                'included_ar' => [
                    'منشور تفصيلي وفيديو مخصص على صفحات وصال (فيسبوك وتيك توك)',
                    'نشر رابط بروفايلك الشخصي على المنصة',
                    'إتاحة التواصل المباشر خارج التطبيق عبر الواتساب للمهتمين',
                    'التحكم الكامل بأسلوب التواصل واختيار ما يناسبك',
                ],
                'included_en' => [
                    'Detailed post and video on Wesal social channels',
                    'Attaching your personal profile link',
                    'Allowing direct external WhatsApp contact for interested users',
                    'Full control over your preferred contact method',
                ],
                'icon' => static_asset('assets/img/vip/social.png'),
                'icon_key' => 'message',
                'sort_order' => 30,
                'allow_external_contact' => true,
                'show_special_thanks' => false,
                'active' => true,
                'archived' => false,
                'is_custom' => false,
            ],
        ];
    }

    /**
     * Merge default services with every service created by the admin.
     * Archived services remain available for historical order labels.
     */
    public static function all(): array
    {
        $storedValue = function_exists('get_setting') ? get_setting(self::SETTING_KEY) : null;
        $stored = is_array($storedValue) ? $storedValue : json_decode((string) $storedValue, true);
        if (!is_array($stored)) {
            $stored = [];
        }

        $storedByType = [];
        foreach ($stored as $item) {
            if (is_array($item) && !empty($item['service_type'])) {
                $storedByType[(string) $item['service_type']] = $item;
            }
        }

        $services = [];
        foreach (self::defaults() as $default) {
            $type = $default['service_type'];
            $saved = $storedByType[$type] ?? [];
            unset($storedByType[$type]);
            $services[] = self::normalise(array_merge($default, $saved));
        }

        foreach ($storedByType as $item) {
            $services[] = self::normalise($item);
        }

        usort($services, function ($left, $right) {
            $order = ((int) ($left['sort_order'] ?? 0)) <=> ((int) ($right['sort_order'] ?? 0));
            return $order !== 0 ? $order : ((int) ($left['id'] ?? 0) <=> (int) ($right['id'] ?? 0));
        });

        return $services;
    }

    private static function normalise(array $service): array
    {
        $service['price'] = max(0, (float) ($service['price'] ?? 0));
        $service['currency'] = '$';
        $service['title_ar'] = trim((string) ($service['title_ar'] ?? $service['title'] ?? ''));
        $service['title_en'] = trim((string) ($service['title_en'] ?? ''));
        $service['title'] = $service['title_ar'];
        $service['promo_ar'] = trim((string) ($service['promo_ar'] ?? $service['description'] ?? ''));
        $service['promo_en'] = trim((string) ($service['promo_en'] ?? ''));
        $service['description'] = $service['promo_ar'];
        $service['included_ar'] = is_array($service['included_ar'] ?? null) ? array_values($service['included_ar']) : [];
        $service['included_en'] = is_array($service['included_en'] ?? null) ? array_values($service['included_en']) : [];
        $service['icon_key'] = array_key_exists((string) ($service['icon_key'] ?? ''), self::iconOptions())
            ? $service['icon_key']
            : 'star';
        $service['sort_order'] = (int) ($service['sort_order'] ?? 100);
        $service['allow_external_contact'] = filter_var($service['allow_external_contact'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $service['show_special_thanks'] = filter_var($service['show_special_thanks'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $service['active'] = filter_var($service['active'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $service['archived'] = filter_var($service['archived'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $service['is_custom'] = filter_var($service['is_custom'] ?? false, FILTER_VALIDATE_BOOLEAN);
        return $service;
    }

    public static function active(): array
    {
        return array_values(array_filter(self::all(), function ($service) {
            return !empty($service['active']) && empty($service['archived']);
        }));
    }

    public static function find(string $serviceType): ?array
    {
        foreach (self::all() as $service) {
            if (($service['service_type'] ?? null) === $serviceType) {
                return $service;
            }
        }
        return null;
    }

    public static function findActive(string $serviceType): ?array
    {
        $service = self::find($serviceType);
        if (!$service || empty($service['active']) || !empty($service['archived'])) {
            return null;
        }
        return $service;
    }

    public static function save(array $services): void
    {
        $payload = json_encode(
            array_values($services),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
        );

        if (!is_string($payload)) {
            throw new \RuntimeException('Unable to encode VIP service catalogue.');
        }

        DB::transaction(function () use ($payload) {
            $setting = Setting::where('type', self::SETTING_KEY)
                ->lockForUpdate()
                ->first();

            if (!$setting) {
                $setting = new Setting();
                $setting->type = self::SETTING_KEY;
            }

            $setting->value = $payload;
            if ($setting->save() !== true) {
                throw new \RuntimeException('VIP service catalogue was not saved.');
            }
        });

        // Flush after the transaction commits, then verify the persisted value.
        Cache::forget('settings');
        $storedPayload = Setting::where('type', self::SETTING_KEY)->value('value');

        if (!is_string($storedPayload)
            || !hash_equals(hash('sha256', $payload), hash('sha256', $storedPayload))) {
            Cache::forget('settings');
            throw new \RuntimeException('VIP service catalogue read-back verification failed.');
        }
    }
}
