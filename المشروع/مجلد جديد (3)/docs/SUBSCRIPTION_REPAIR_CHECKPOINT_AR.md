# نقطة استئناف – إصلاح دورة اشتراكات وصال

التاريخ: 2026-11-04  
المستودع: `alialahmad9899/AAA`  
الفرع: `fix/profile-card-and-editor-alignment-2026-11-03`  
نسخة الفرع التي تمت مراجعتها: `0b532b5e607c065c0c941e2b6179a10a2ebef999`

## المنجز

- [x] استخراج وفحص ملفات Laravel من أرشيف الاستضافة الموجود في المشروع.
- [x] اعتماد تحقق موحد: الباقة المدفوعة يجب أن تكون موجودة وغير محذوفة وفعالة وصلاحيتها موجبة وتاريخها ساري حتى نهاية اليوم.
- [x] جعل تاريخ الانتهاء شاملاً لليوم الأخير، وتجديد الباقة النشطة يبدأ في اليوم التالي لانتهائها.
- [x] إضافة أمر `wesal:expire-member-packages` لتنظيف الاشتراك المنتهي/المعطل/المحذوف وإلغاء حالة Premium وربط الباقة والحصص المدفوعة وميزة التطابق الذكي.
- [x] إضافة `wesal:notify-expiring-member-packages` لتذكير قبل يوم واحد، مع سجل فريد لإشعار التطبيق ومحاولات Push منفصلة.
- [x] جدولة التنظيف اليومي عند 00:10 والتذكير كل ساعة بعد 09:00 بحسب `APP_TIMEZONE`.
- [x] إزالة مسار التنظيف القديم المكشوف عبر GET.
- [x] إضافة إلغاء فوري لباقة عضو محدد من لوحة الإدارة مع صلاحية `update_member_package` وسجل حدث وإشعار العضو.
- [x] تصحيح قائمتي Premium والمجاني في لوحة الإدارة والموقع والـ API لتعتمدا على باقة مدفوعة سارية، لا على علم `membership` وحده، وإصلاح حجب صورة الملف وفق الصلاحية الحقيقية مع بقاء النساء مجاناً.
- [x] توحيد فحوص ميزات الاهتمام/التواصل/التطابق/الصور/المعرض والحصص.
- [x] الحفاظ على سياسة الاستخدام المجاني للنساء وعدم إظهارها كاشتراك Premium مدفوع.
- [x] توحيد إنشاء المحادثات على الموقع والـ API، وفرض حد عدد المستلمين الجدد حسب الباقة.
- [x] إيقاف إرسال الرسائل للعضو غير المؤهل بعد انتهاء الاشتراك، ومنع IDOR على المحادثات وسجل الرسائل القديمة.
- [x] قفل سجلات المستخدم والعضو والدفع عند تفعيل الباقة ومنع تكرار التفعيل بمفتاح فريد.
- [x] جعل الموافقة على الدفع اليدوي مقيدة بحالة `Due` وصلاحية إدارة الدفعات.
- [x] التحقق من Stripe PaymentIntent وCheckout Session قبل التفعيل.
- [x] Native Stripe يستخدم `system_default_currency` نفسه الذي يستخدمه الموقع، ويرجع API رمز العملة إلى Flutter بدلاً من افتراض الدولار، و`ApiService` يحافظ على حقول العملة حتى شاشة الاشتراك.
- [x] التحقق من aamarPay لدى المزود ومطابقة الحالة والمعرّف والمتجر والمبلغ والعملة. تم إلغاء فرض BDT الثابت؛ يبدأ الدفع بعملة `system_default_currency` الموقعة ويتحقق منها عند callback. التكامل يقبل حالياً USD/BDT فقط ويرفض غيرهما.
- [x] جعل شحن المحفظة عبر Stripe وaamarPay idempotent برقم المعاملة.
- [x] إصلاح عرض تاريخ الانتهاء في Flutter ليظل صالحاً خلال يوم الانتهاء، وإظهار الباقة المجانية النسائية دون تاريخ انتهاء وهمي.
- [x] إضافة/تحديث اختبارات ساكنة وسجل استئناف داخل المشروع.

## ملفات محورية

- `app/Utility/SubscriptionUtility.php`
- `app/Utility/MemberUtility.php`
- `app/Services/SubscriptionNotificationService.php`
- `app/Services/GatewayWalletCreditService.php`
- `app/Console/Commands/ExpireMemberPackages.php`
- `app/Console/Commands/NotifyExpiringMemberPackages.php`
- `app/Console/Kernel.php`
- `app/Http/Controllers/ChatController.php`
- `app/Http/Controllers/Api/ChatController.php`
- `app/Http/Controllers/Api/PackageController.php` و`app/Http/Controllers/Api/Payment/StripeController.php`
- Flutter: `lib/services/api_service.dart` و`lib/screens/home/tabs/account_tab.dart`
- `database/migrations/2026_11_04_000000_add_subscription_lifecycle_tracking.php`
- Stripe/aamarPay controllers، مسارات الـ API والويب، وملفات Flutter المدرجة في الحزمة.

## الاختبارات

- `php -l` لكل ملفات PHP داخل الحزمة: 44 ملفاً، ناجح.
- اختبارات primitives للحساب والحصص: `10/10`.
- اختبارات التكامل الساكنة: `68/68`، بعد إضافة فحوص عملة aamarPay وحقل العملة في سجل المحفظة، إلى جانب فلاتر قوائم العضويات وخصوصية الصور.
- لم تتوفر قاعدة بيانات الإنتاج أو مفاتيح بوابات الدفع أو بيئة Laravel/Composer كاملة، لذلك لا يوجد ادعاء باختبار تكامل حي. لم نقرأ قيمة العملة الفعلية من DB الإنتاج أو إعداد حساب aamarPay؛ يجب التأكد أن العملة الفعلية USD أو BDT وأنها مفعلة لدى التاجر. يجب أيضاً أن تكون عملة Stripe مدعومة وبنظام وحدات فرعية من منزلتين عشريتين؛ فطريقة احتساب السعر الحالية تتبع طريقة الموقع الموجودة (`price × 100`).
- لم تتوفر أدوات `flutter` و`dart` في بيئة الفحص، لذلك يجب تشغيل `flutter test` في بيئة المشروع.

## المطلوب قبل الإنتاج

1. تحقق من `packages.validity` في DB: الأسبوعية 7، الشهرية 30، الثلاثة أشهر 90.
2. خذ نسخة احتياطية، انسخ الملفات، شغّل `php artisan migrate --force` ثم `php artisan optimize:clear`.
3. أضف Cron كل دقيقة لتشغيل `php artisan schedule:run`.
4. تأكد من `APP_TIMEZONE`، ومفاتيح Stripe/aamarPay، وإعداد FCM، وتطابق `system_default_currency` مع العملة المفعلة في حساب aamarPay (USD/BDT).
5. اختبر Staging: تاريخ انتهى أمس، انتهاء اليوم، تذكير الغد مرتين، التجديد قبل النهاية، الإلغاء الإداري، إنشاء محادثات جديدة وحد المستلمين، وإعادة Callback.

**لم يتم رفع التعديلات إلى الاستضافة أو دمج الفرع في `main`.**
