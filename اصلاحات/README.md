# ملفات الإصلاحات

هذا المجلد يجمع نسخاً من جميع الملفات التي عُدّلت ضمن إصلاح عملية التسجيل في تطبيق وصال.

## Flutter
- `Flutter/lib/screens/signup_screen.dart` — شاشة التسجيل والتحقق من نجاح حفظ المراحل.
- `Flutter/lib/services/api_service.dart` — إرسال التسجيل والصورة والتحقق من استجابات API.
- `Flutter/test/profile_edit_unit_test.dart` — اختبارات استجابات حفظ البيانات.
- `Flutter/test/widget_test.dart` — اختبار بناء مسار التطبيق الفعلي.

## Laravel
- `Laravel/app/Http/Controllers/Api/AuthController.php` — إنشاء المستخدم والعضوية والصورة وحالة التحقق ضمن معاملة واحدة.
- `Laravel/app/Http/Requests/AuthRequest.php` — قواعد التحقق من الصورة وحجمها وامتداداتها.

## CI
- `CI/signup-validation.yml` — إعدادات فحص PHP وتحليل Flutter والاختبارات.

هذه نسخ مرجعية مجمعة؛ ملفات Flutter الأصلية تبقى في مساراتها التشغيلية تحت `lib/` و`test/`. وملفا Laravel مستخرجان من أرشيف الاستضافة المحدّث، ومسار نشرهما على الخادم يبقى منفصلاً.
