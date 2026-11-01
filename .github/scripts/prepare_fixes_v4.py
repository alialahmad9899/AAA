#!/usr/bin/env python3
from pathlib import Path
import json, shutil, zipfile
ROOT=Path('.')
SOURCE=ROOT/'اصلاحات 3'
OUT=ROOT/'اصلاحات 4'
def main():
    if not (SOURCE/'README.md').is_file(): raise SystemExit('Validated اصلاحات 3 baseline is missing')
    shutil.rmtree(OUT, ignore_errors=True)
    shutil.copytree(SOURCE, OUT)
    manifest_path=OUT/'hosting-patch-manifest.json'
    manifest=json.loads(manifest_path.read_text(encoding='utf-8'))
    manifest['version']=4
    manifest['release']='اصلاحات 4'
    manifest['changes']=['Signup verification bypassed for Wesal; server logs detailed signup exception diagnostics; logout revokes current Sanctum token and clears account FCM token; includes prior اصلاحات 3 fixes.']
    manifest_path.write_text(json.dumps(manifest,ensure_ascii=False,indent=2)+'\\n',encoding='utf-8')
    (OUT/'Laravel/app/Http/Controllers/Api/AuthController.php').write_text((SOURCE/'Laravel/app/Http/Controllers/Api/AuthController.php').read_text(encoding='utf-8'),encoding='utf-8')
    (OUT/'README.md').write_text('''# اصلاحات 4 — Wesal End-to-End fixes

- تعطيل تحقق البريد/OTP في التسجيل؛ الحساب يصبح موثقاً لمتابعة خطوات الملف الشخصي.
- تسجيل تفاصيل استثناء التسجيل في Laravel logs: النوع والرسالة والملف والسطر وstack trace، دون كشفها للعميل.
- تسجيل الخروج يلغي توكن Sanctum الحالي ويفصل FCM token عن الحساب.
- تتضمن ملفات إصلاحات 3 السابقة: حفظ الملف والصورة، الهاتف الاختياري، حذف سنوات بدء التعليم والعمل، ومسارات API وFlutter السابقة.

## النشر والاختبار
1. خذ نسخة احتياطية من Laravel وقاعدة البيانات.
2. انسخ ملفات Laravel وFlutter مع الحفاظ على المسارات؛ أعد بناء التطبيق.
3. بعد إعادة محاولة التسجيل، راجع storage/logs/laravel.log لمعرفة السبب الأصلي لـ HTTP 500.
4. اختبر على staging: إنشاء حساب وصورة، كل خطوات الملف، الباقات، الاهتمامات، الزيارات، المحادثات، والخروج.
5. لا تعتبر CI اختباراً حياً أو اختبار دفع حقيقي؛ استخدم حسابين وبيئة دفع sandbox.
''',encoding='utf-8')
    archive=ROOT/'اصلاحات 4.zip'
    if archive.exists(): archive.unlink()
    with zipfile.ZipFile(archive,'w',zipfile.ZIP_DEFLATED,compresslevel=9) as z:
        for f in sorted(OUT.rglob('*')):
            if f.is_file(): z.write(f,f.relative_to(ROOT).as_posix())
    with zipfile.ZipFile(archive) as z:
        bad=z.testzip()
        if bad: raise RuntimeError('ZIP integrity error: '+bad)
    print('Built اصلاحات 4.zip')
if __name__=='__main__': main()