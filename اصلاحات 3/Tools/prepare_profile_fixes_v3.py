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

def patch_signup_member_mapping(source):
    old = """                $request->merge(['user_id' => $user->id]);
                $member_service->store(
                    $request->only(['gender', 'birthday', 'on_behalves_id', 'user_id']),
                    $package
                );
"""
    new = """                // Normalize the field names sent by the Flutter signup form.
                // The app sends date_of_birth and on_behalf; MemberService expects
                // birthday and on_behalves_id.
                $memberData = [
                    'gender' => $request->input('gender'),
                    'birthday' => $request->input('date_of_birth') ?? $request->input('birthday'),
                    'on_behalves_id' => $request->input('on_behalf') ?? $request->input('on_behalves_id'),
                    'user_id' => $user->id,
                ];
                $member_service->store($memberData, $package);
"""
    if new in source:
        return source
    count = source.count(old)
    if count != 1:
        raise RuntimeError(f"AuthController signup field mapping: expected one original block, found {count}")
    return source.replace(old, new, 1)


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
            "app/Http/Controllers/Api/AuthController.php":patch_signup_member_mapping,
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

    # Bring forward the complete Laravel baseline from اصلاحات 2, including
    # ProfileController and the photo/basic-profile transaction fixes.
    baseline_laravel = Path("اصلاحات 2/Laravel")
    if not baseline_laravel.is_dir():
        raise RuntimeError("The validated Laravel baseline in اصلاحات 2 is missing")
    for source_file in sorted(baseline_laravel.rglob("*.php")):
        relative = source_file.relative_to(baseline_laravel)
        destination = OUT/"Laravel"/relative
        destination.parent.mkdir(parents=True,exist_ok=True)
        destination.write_bytes(source_file.read_bytes())

    # Overlay the current v3 Laravel updates, including optional phone, year
    # removal, and the signup field-name normalization.
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
      "lib/screens/partner_expectations_screen.dart",
      "lib/screens/widgets/step2_personal_details.dart",
      "lib/screens/edit_profile_screen.dart",
      "lib/screens/edit_profile/widgets/step1_basic_physical_tab.dart",
      "lib/services/api_service.dart",
      "lib/models/member_profile_model.dart",
      "test/profile_edit_unit_test.dart",
      "test/widget_test.dart",
    ]
    # Include the previously repaired supporting Flutter files, then always
    # overwrite every file touched in this v3 pass with the current main source.
    baseline_flutter = Path("اصلاحات 2/Flutter")
    if not baseline_flutter.is_dir():
        raise RuntimeError("The validated Flutter baseline in اصلاحات 2 is missing")
    for source_file in sorted(baseline_flutter.rglob("*")):
        if not source_file.is_file():
            continue
        relative = source_file.relative_to(baseline_flutter)
        destination = OUT/"Flutter"/relative
        destination.parent.mkdir(parents=True,exist_ok=True)
        destination.write_bytes(source_file.read_bytes())

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
    laravel_manifest = sorted(
        item.relative_to(OUT/"Laravel").as_posix()
        for item in (OUT/"Laravel").rglob("*.php")
    )
    flutter_manifest = sorted(
        item.relative_to(OUT/"Flutter").as_posix()
        for item in (OUT/"Flutter").rglob("*") if item.is_file()
    )
    manifest={"version":3,"hosting_archive":ARCHIVE.name,
       "laravel_files":laravel_manifest,
       "flutter_files":flutter_manifest}
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
