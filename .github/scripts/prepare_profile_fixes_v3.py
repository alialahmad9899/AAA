#!/usr/bin/env python3
from __future__ import annotations
import json, re, zipfile
from pathlib import Path

ARCHIVE=Path("ملفات الاتسضافة.zip")
OUT=Path("اصلاحات 3")

def entry_for(names, suffix):
    found=[n for n in names if n.endswith(suffix)]
    if len(found)!=1: raise RuntimeError(f"{suffix}: expected one archive entry, got {len(found)}")
    return found[0]

def patch_auth(source):
    source,n=re.subn(r"(?m)^(\s*'phone'\s*=>\s*)'[^']*'(\s*,?\s*)$",
        r"\1'sometimes|nullable|string|max:32|unique:users,phone'\2",source,count=1)
    if n==0 and not re.search(r"'phone'\s*=>\s*'[^']*(?:sometimes|nullable)",source):
        raise RuntimeError("AuthRequest phone rule not found")
    if re.search(r"'phone'\s*=>\s*'[^']*required",source):
        raise RuntimeError("AuthRequest phone still required")
    return source

def patch_profile_request(source):
    source,n=re.subn(r"(?m)^(\s*'phone'\s*=>\s*)\[[^\n]*\](\s*,?\s*)$",
        r"\1['sometimes','nullable','string','max:32']\2",source,count=1)
    if n==0 and not re.search(r"'phone'\s*=>\s*\[[^\]]*'sometimes'[^]]*'nullable'",source):
        raise RuntimeError("ProfileRequest phone rule not optional")
    if re.search(r"'phone'\s*=>\s*\[[^\]]*'required'",source):
        raise RuntimeError("ProfileRequest phone still required")
    return source

def patch_education(source):
    source=re.sub(r"(?m)^\s*'education_start'\s*=>\s*\[[^\n]*\],?\s*\n","",source)
    source=re.sub(r"(?m)^\s*(?:if\s*\(.*\)\s*)?\$education->start\s*=.*;\s*\n","",source)
    if "education_start" in source or "$education->start" in source:
        raise RuntimeError("EducationController still validates or stores start year")
    return source

def patch_career_request(source):
    source=re.sub(r"(?m)^\s*\$startRule\s*=.*;\s*\n","",source)
    source=re.sub(r"(?m)^\s*'start'\s*=>\s*\[[^\n]*\],?\s*\n","",source)
    source=re.sub(r"(?m)^\s*'start\.(?:required|numeric)'\s*=>.*\n","",source)
    if "$startRule" in source or re.search(r"'start'\s*=>",source):
        raise RuntimeError("CareerRequest still validates career start year")
    return source

def patch_resource(source,label):
    source=re.sub(r"(?m)^\s*['\"](?:start|education_start|career_start)['\"]\s*=>.*\n","",source)
    if re.search(r"['\"](?:start|education_start|career_start)['\"]\s*=>",source):
        raise RuntimeError(f"{label} still returns start year")
    return source

def migration():
    return """<?php
use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration {
    public function up(): void
    {
        foreach (['education', 'educations', 'career', 'careers'] as $table) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'start')) continue;
            $column = DB::selectOne(
                'SELECT COLUMN_TYPE AS column_type FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
                [$table, 'start']
            );
            $type = $column->column_type ?? null;
            if (!is_string($type) || !preg_match('/^[A-Za-z0-9_(), ]+$/', $type)) {
                throw new RuntimeException('Cannot safely inspect start column type for ' . $table);
            }
            DB::statement('ALTER TABLE ' . $table . ' MODIFY start ' . $type . ' NULL');
        }
    }
    public function down(): void
    {
        // Keep fields nullable; new profile records may not contain start years.
    }
};
"""

def main():
    if not ARCHIVE.is_file(): raise SystemExit("Hosting ZIP not found")
    with zipfile.ZipFile(ARCHIVE,"r") as z:
        names=z.namelist()
        patchers={
            "app/Http/Requests/AuthRequest.php":patch_auth,
            "app/Http/Requests/ProfileRequest.php":patch_profile_request,
            "app/Http/Controllers/Api/EducationController.php":patch_education,
            "app/Http/Requests/CareerRequest.php":patch_career_request,
            "app/Http/Resources/EducationResource.php":lambda x:patch_resource(x,"EducationResource"),
            "app/Http/Resources/CareerResource.php":lambda x:patch_resource(x,"CareerResource"),
        }
        changes={}
        for suffix,fn in patchers.items():
            name=entry_for(names,suffix)
            src=z.read(name).decode("utf-8").replace("\r\n","\n")
            changes[name]=fn(src).encode("utf-8")
        auth=entry_for(names,"app/Http/Controllers/Api/AuthController.php")
        prefix=auth[:-len("app/Http/Controllers/Api/AuthController.php")]
        migration_path=prefix+"database/migrations/2026_11_01_000000_make_profile_start_years_nullable.php"
        migration_text=migration()
        temp=ARCHIVE.with_suffix(".v3.tmp.zip")
        try:
            with zipfile.ZipFile(temp,"w",compression=zipfile.ZIP_DEFLATED) as out:
                for info in z.infolist():
                    if info.filename==migration_path: continue
                    data=changes.get(info.filename)
                    if data is None: data=z.read(info.filename)
                    out.writestr(info,data)
                out.writestr(migration_path,migration_text.encode())
            with zipfile.ZipFile(temp,"r") as check:
                bad=check.testzip()
                if bad: raise RuntimeError("Invalid ZIP entry: "+bad)
            temp.replace(ARCHIVE)
        finally:
            if temp.exists(): temp.unlink()

    for suffix in patchers:
        name=entry_for(names,suffix)
        dest=OUT/"Laravel"/suffix
        dest.parent.mkdir(parents=True,exist_ok=True)
        dest.write_bytes(changes[name])
    dest=OUT/"Laravel"/"database/migrations/2026_11_01_000000_make_profile_start_years_nullable.php"
    dest.parent.mkdir(parents=True,exist_ok=True)
    dest.write_text(migration_text,encoding="utf-8")

    flutter=[
      "lib/screens/signup_screen.dart",
      "lib/screens/widgets/step2_personal_details.dart",
      "lib/screens/edit_profile_screen.dart",
      "lib/screens/edit_profile/widgets/step1_basic_physical_tab.dart",
      "lib/services/api_service.dart",
      "lib/models/member_profile_model.dart",
      "test/profile_edit_unit_test.dart",
    ]
    for rel in flutter:
        src=Path(rel)
        if not src.is_file(): raise RuntimeError("Missing Flutter source: "+rel)
        dst=OUT/"Flutter"/rel
        dst.parent.mkdir(parents=True,exist_ok=True)
        dst.write_bytes(src.read_bytes())
    tools=OUT/"Tools"; tools.mkdir(parents=True,exist_ok=True)
    for rel in [".github/scripts/patch_hosting_archive.py",
                ".github/scripts/prepare_profile_fixes_v3.py",
                ".github/workflows/signup-validation.yml"]:
        (tools/Path(rel).name).write_bytes(Path(rel).read_bytes())
    manifest={"version":3,"hosting_archive":ARCHIVE.name,
       "laravel_files":sorted(list(patchers.keys())+["database/migrations/2026_11_01_000000_make_profile_start_years_nullable.php"]),
       "flutter_files":flutter}
    (OUT/"hosting-patch-manifest.json").write_text(json.dumps(manifest,ensure_ascii=False,indent=2)+"\n",encoding="utf-8")
    (OUT/"README.md").write_text("""# اصلاحات 3

تشمل الحزمة ملفات Flutter وLaravel وأرشيف الاستضافة بعد التعديل.

- رقم الهاتف اختياري في التسجيل وتحديث الملف.
- أزيلت سنة بدء التعليم وسنة بدء العمل من التطبيق وطلبات API والتحقق والاستجابات.
- Migration تجعل أعمدة start القديمة اختيارية وتحافظ على البيانات الموجودة.
- الجلب الموحّد للملف الشخصي يُستخدم أولاً لتحسين سرعة شاشة التعديل، مع رجوع تلقائي للطلبات القديمة عند عدم توفره.

## النشر
1. خذ نسخة احتياطية من ملفات Laravel وقاعدة البيانات.
2. انسخ محتويات Laravel إلى جذر Laravel مع الحفاظ على المسارات.
3. نفّذ php artisan migrate --force من جذر Laravel.
4. اختبر إنشاء حساب دون رقم هاتف، ثم تعديل الملف والصورة مع ترك الهاتف فارغاً، ثم أعد فتح الملف.
5. انسخ Flutter إلى مشروع التطبيق مع الحفاظ على المسارات، ثم نفّذ flutter pub get وflutter test وابنِ إصداراً جديداً.

فحوصات CI لا تعادل اختباراً حياً على قاعدة بيانات الإنتاج؛ يتطلب ذلك رفع الملفات وتشغيل migration ثم تجربة حساب فعلي.
""",encoding="utf-8")
    print("Prepared اصلاحات 3")

if __name__=="__main__": main()
