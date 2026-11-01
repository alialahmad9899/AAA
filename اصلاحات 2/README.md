# اصلاحات 2 — إصلاحات التسجيل والملف الشخصي وربط بيانات العضو

يحتوي هذا المجلد على الملفات النهائية المطابقة لما تم فحصه في CI.

## Flutter
- `Flutter/lib/screens/signup_screen.dart` — تدفق التسجيل المرحلي، والتحقق من الحقول، وعدم تمرير تفضيلات العضو على أنها تفضيلات الشريك.
- `Flutter/lib/screens/edit_profile_screen.dart` — تحميل البيانات ومعرّفات السجلات وتحديث الملف الشخصي والصورة.
- `Flutter/lib/screens/partner_expectations_screen.dart` — تحميل وحفظ تفضيلات الشريك دون قيم افتراضية مختلقة.
- `Flutter/lib/screens/widgets/step2_personal_details.dart` — حقول التعليم والعمل وسنوات البدء.
- `Flutter/lib/screens/edit_profile/widgets/step1_basic_physical_tab.dart` — حقول تعديل التعليم والعمل وسنوات البدء.
- `Flutter/lib/services/api_service.dart` — فك استجابات Laravel والتحقق الصارم من نجاح الكتابة وإرسال الحقول المقدمة فقط.
- `Flutter/test/` — اختبارات استجابات الكتابة وموارد Laravel واختبار بناء التطبيق.

## Laravel — ملفات يمكن نسخها إلى المسارات النسبية نفسها داخل جذر Laravel
- `Laravel/app/Http/Controllers/Api/AuthController.php`
- `Laravel/app/Http/Controllers/Api/ProfileController.php`
- `Laravel/app/Http/Controllers/Api/EducationController.php`
- `Laravel/app/Http/Controllers/Api/CareerController.php`
- `Laravel/app/Http/Requests/ProfileRequest.php`
- `Laravel/app/Http/Requests/CareerRequest.php`
- `Laravel/app/Http/Requests/PartnerExpectationRequest.php`
- `Laravel/app/Http/Resources/PublicProfile/BasicInformation.php`
- `Laravel/app/Http/Resources/PublicProfile/SpiritualSocialBackground.php`
- `Laravel/app/Http/Resources/PublicProfile/PartnerExpectationResource.php`

كما يحتوي جذر المستودع على أرشيف الاستضافة `ملفات الاتسضافة.zip` بعد تطبيق التعديلات نفسها.

## أدوات الفحص
- `Tools/patch_hosting_archive.py` — يعيد تطبيق التعديلات على أرشيف الاستضافة مع فشل صريح عند عدم مطابقة المصدر.
- `CI/signup-validation.yml` — تحليل Flutter والاختبارات وفحص PHP وسلامة الأرشيف.

**مهم:** هذه الملفات مرفوعة على GitHub ومفحوصة بناءً على المصدر المتاح في الأرشيف. لا يعني ذلك أنها نُشرت تلقائياً على الاستضافة الحية أو أنها اختُبرت على قاعدة البيانات الحية. خذ نسخة احتياطية قبل استبدال أي ملف على الخادم ثم نفّذ اختبار تسجيل وتعديل فعلي.
