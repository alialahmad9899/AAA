<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class FirebaseNotificationService
{
    /**
     * دالة تشفير Base64URL المعتمدة لدى Google OAuth 2.0
     */
    private static function base64UrlEncode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * إرسال إشعار فوري عبر Firebase FCM v1 API مع دعم الحقول المخصصة (Custom Payload Data)
     * 
     * @param string $targetFcmToken توكن الجهاز المستهدف
     * @param string $title عنوان الإشعار
     * @param string $body نص الإشعار
     * @param array $extraData مصفوفة البيانات المخصصة (مثل: type, thread_id, route, photo, sender_id)
     * @return bool نجاح أو فشل الإرسال
     */
    public static function sendPushNotification($targetFcmToken, $title, $body, array $extraData = [])
    {
        // 1. التحقق من وجود ملف حساب الخدمة الخاص بـ Firebase (service-account.json)
        $jsonKeyPath = app_path('service-account.json');

        if (!file_exists($jsonKeyPath)) {
            Log::error("❌ [FCM Error]: ملف Firebase service-account.json غير موجود في المسار: " . $jsonKeyPath);
            return false;
        }

        $jsonData = json_decode(file_get_contents($jsonKeyPath), true);

        if (!$jsonData || !isset($jsonData['client_email']) || !isset($jsonData['private_key']) || !isset($jsonData['project_id'])) {
            Log::error("❌ [FCM Error]: بيانات ملف service-account.json غير مكتملة.");
            return false;
        }

        // معالجة المفتاح الخاص لضمان التنسيق الصحيح للأسطر الجديدة
        $privateKey = str_replace('\n', "\n", $jsonData['private_key']);

        // 2. بناء وتوقيع JWT Token للحصول على صلاحيات الإرسال من Google
        $jwtHeader = self::base64UrlEncode(json_encode(["alg" => "RS256", "typ" => "JWT"]));
        $now = time();
        $jwtClaim = self::base64UrlEncode(json_encode([
            "iss" => $jsonData['client_email'],
            "scope" => "https://www.googleapis.com/auth/firebase.messaging",
            "aud" => "https://oauth2.googleapis.com/token",
            "exp" => $now + 3600,
            "iat" => $now
        ]));

        $signatureInput = $jwtHeader . "." . $jwtClaim;

        // التوقيع باستخدام OpenSSL ومفتاح غوغل الخاص
        $signatureResult = openssl_sign($signatureInput, $signature, $privateKey, "SHA256");
        if (!$signatureResult) {
            Log::error("❌ [FCM Error]: فشل توقيع OpenSSL لمفتاح Firebase.");
            return false;
        }

        $jwt = $signatureInput . "." . self::base64UrlEncode($signature);

        // 3. طلب Access Token مؤقت من Google OAuth 2.0
        $ch = curl_init("https://oauth2.googleapis.com/token");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt
        ]));

        $rawTokenResponse = curl_exec($ch);
        $tokenResponse = json_decode($rawTokenResponse, true);
        curl_close($ch);

        if (!isset($tokenResponse['access_token'])) {
            Log::error("❌ [FCM Error]: فشل الحصول على Access Token من Google: " . $rawTokenResponse);
            return false;
        }

        $accessToken = $tokenResponse['access_token'];

        // 4. تجهيز وتنظيف مصفوفة البيانات المخصصة وضمان تحويل جميع القيم إلى نصوص (Strings)
        $formattedExtraData = [];
        foreach ($extraData as $key => $value) {
            if ($value !== null) {
                $formattedExtraData[(string) $key] = (string) $value;
            }
        }

        // الحقول الإضافية الافتراضية المرفقة دائماً مع التنبيه
        $defaultData = [
            "title"        => (string) $title,
            "message"      => (string) $body,
            "click_action" => "FLUTTER_NOTIFICATION_CLICK",
        ];

        // دمج الحقول الأساسية مع الحقول المخصصة الممررة
        $dataPayload = array_merge($defaultData, $formattedExtraData);

        $type = strtolower((string) ($formattedExtraData['type'] ?? ''));
        $isChatNotification = str_contains($type, 'chat')
            || str_contains($type, 'message')
            || str_contains($type, 'conversation');
        $isSilent = in_array(
            strtolower((string) ($formattedExtraData['silent'] ?? 'false')),
            ['1', 'true', 'yes'],
            true
        );
        $notificationTag = $formattedExtraData['notification_tag'] ?? null;

        // 5. Android channels are split so quiet replacements remain quiet.
        $androidNotificationConfig = [
            "channel_id" => $isChatNotification
                ? ($isSilent ? "wesal_chat_silent_channel" : "wesal_chat_alert_channel")
                : "wesal_high_importance_channel",
            "icon" => "@mipmap/ic_launcher",
            "notification_priority" => $isSilent ? "PRIORITY_LOW" : "PRIORITY_MAX"
        ];
        if (!$isSilent) {
            $androidNotificationConfig["sound"] = "default";
            $androidNotificationConfig["default_sound"] = true;
            $androidNotificationConfig["default_vibrate_timings"] = true;
        } else {
            $androidNotificationConfig["default_sound"] = false;
            $androidNotificationConfig["default_vibrate_timings"] = false;
        }
        if (!empty($notificationTag)) {
            $androidNotificationConfig["tag"] = substr((string) $notificationTag, 0, 64);
        }

        // في حال توفر رابط صورة، يتم إدراجها ضمن إشعار أندرويد لظهورها بصورة موسعة
        if (isset($formattedExtraData['photo']) && !empty($formattedExtraData['photo'])) {
            $androidNotificationConfig['image'] = $formattedExtraData['photo'];
        }

        // 6. بناء شحنة الإشعار الكلية (Payload)
        $url = "https://fcm.googleapis.com/v1/projects/" . $jsonData['project_id'] . "/messages:send";

        $payload = [
            "message" => [
                "token" => $targetFcmToken,
                
                // العرض المباشر في ستارة وشريط النظام
                "notification" => [
                    "title" => (string) $title,
                    "body"  => (string) $body,
                ],

                // البيانات المخصصة التي يقرأها تطبيق الجوال عند فتح الإشعار
                "data" => $dataPayload,

                // إعدادات نظام Android
                "android" => [
                    "priority" => "HIGH",
                    "notification" => $androidNotificationConfig
                ],

                // إعدادات نظام iOS
                "apns" => [
                    "payload" => [
                        "aps" => [
                            "alert" => [
                                "title" => (string) $title,
                                "body"  => (string) $body,
                            ],
                        ]
                    ]
                ]
            ]
        ];

        if (!$isSilent) {
            $payload['message']['apns']['payload']['aps']['sound'] = 'default';
        }
        if (!$isChatNotification) {
            $payload['message']['apns']['payload']['aps']['badge'] = 1;
        }
        if ($isChatNotification && !empty($notificationTag)) {
            $payload['message']['apns']['headers'] = [
                'apns-collapse-id' => substr((string) $notificationTag, 0, 64),
            ];
            $payload['message']['apns']['payload']['aps']['thread-id'] =
                (string) $notificationTag;
        }

        // إرفاق الصورة في إشعار أجهزة iOS إن وجدت
        if (isset($formattedExtraData['photo']) && !empty($formattedExtraData['photo'])) {
            $payload['message']['apns']['fcm_options'] = [
                'image' => $formattedExtraData['photo']
            ];
        }

        // 7. إرسال الطلب الهوائي عبر cURL
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . $accessToken,
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // 8. معالجة وتدوين النتيجة
        if ($httpCode !== 200) {
            Log::error("❌ [FCM Send Error]: فشل الإرسال الهوائي. رمز الحالة: $httpCode | الرد: $response");
            return false;
        }

        Log::info("✅ [FCM Success]: تم إرسال الإشعار بالحقول المخصصة بنجاح إلى FCM Token!");
        return true;
    }
}