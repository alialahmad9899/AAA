#!/usr/bin/env python3
"""Build the API-only follow-up patch for Wesal public member-card privacy and partner preferences.

Run after patch_hosting_archive.py and prepare_profile_fixes_v3.py.
This script never deploys or pushes files.
"""
from __future__ import annotations

import json
import re
import shutil
from pathlib import Path

SOURCE = Path("اصلاحات 3/Laravel")
OUT = Path("اصلاحات 4")
LARAVEL = OUT / "Laravel"


def replace_once(source: str, old: str, new: str, label: str) -> str:
    if new in source:
        return source
    count = source.count(old)
    if count != 1:
        raise RuntimeError(f"{label}: expected one source anchor, found {count}")
    return source.replace(old, new, 1)


def patch_private_profile(source: str) -> str:
    start = source.find("    public function get_full_my_profile()")
    end = source.find("    public function profile_settings()", start)
    if start < 0 or end < 0:
        raise RuntimeError("Could not isolate get_full_my_profile")
    method = source[start:end]

    method = replace_once(
        method,
        "        $data = [",
        """        // Do not return retired family names or dietary preference fields.
        // Contact fields remain private and available to the account owner.
        $ownerLifestyle = null;
        if ($user->lifestyles) {
            $ownerLifestyle = (new LifeStyleResource($user->lifestyles))->toArray(request());
            unset($ownerLifestyle['diet']);
        }

        $data = [""",
        "private profile sanitized lifestyle",
    )
    method = replace_once(
        method,
        "            'lifestyles'           => $user->lifestyles ? new LifeStyleResource($user->lifestyles) : null,",
        "            'lifestyles'           => $ownerLifestyle,",
        "private lifestyle resource",
    )
    method, removed = re.subn(r"(?m)^[ \t]*'families_information'\s*=>.*(?:\n|$)", "", method, count=1)
    if removed != 1:
        raise RuntimeError("Expected exactly one private families_information field")
    if "families_information" in method:
        raise RuntimeError("Private full profile still returns family names")
    if "unset($ownerLifestyle['diet'])" not in method:
        raise RuntimeError("Private full profile still returns diet")
    if "'contact_details'" not in method or "$user->email" not in method or "$user->phone" not in method:
        raise RuntimeError("Private owner contact details must remain available")
    return source[:start] + method + source[end:]


def patch_legacy_fields(source: str) -> str:
    old_life_style = """    public function life_style()
    {
        if (auth()->user()->lifestyles) {
            return (new LifeStyleResource(auth()->user()->lifestyles))->additional([
                'result' => true
            ]);
        } else {
            return $this->failure_message('No Data Found!!');
        }
    }"""
    new_life_style = """    public function life_style()
    {
        $lifestyle = auth()->user()->lifestyles;
        if (!$lifestyle) {
            return $this->failure_message('No Data Found!!');
        }

        $data = (new LifeStyleResource($lifestyle))->toArray(request());
        unset($data['diet']);
        return response()->json(['result' => true, 'data' => $data]);
    }"""
    source = replace_once(source, old_life_style, new_life_style, "legacy lifestyle response")

    old_life_update = """    public function life_style_update(Request $request)
    {
        $lifestyle = Lifestyle::where('user_id', auth()->id())->first();
        if (empty($lifestyle)) {
            $lifestyle              = new Lifestyle;
            $lifestyle->user_id    = auth()->id();
        }
        $lifestyle->diet          = $request->diet;
        $lifestyle->drink         = $request->drink;
        $lifestyle->smoke         = $request->smoke;
        $lifestyle->living_with   = $request->living_with;
        $lifestyle->save();
        return $this->success_message('Lifestyle info has been updated successfully');
    }"""
    new_life_update = """    public function life_style_update(Request $request)
    {
        $lifestyle = Lifestyle::where('user_id', auth()->id())->first();
        if (empty($lifestyle)) {
            $lifestyle = new Lifestyle;
            $lifestyle->user_id = auth()->id();
        }

        // Diet has been retired from the app. Ignore legacy diet input and
        // preserve any old database value without exposing or updating it.
        foreach (['drink', 'smoke', 'living_with'] as $field) {
            if ($request->exists($field)) {
                $lifestyle->{$field} = $request->input($field);
            }
        }

        $lifestyle->save();
        return $this->success_message('Lifestyle info has been updated successfully');
    }"""
    source = replace_once(source, old_life_update, new_life_update, "legacy lifestyle update")

    old_family = """    public function family_info()
    {
        if (auth()->user()->families) {
            return (new FamilyInformation(auth()->user()->families))->additional([
                'result' => true
            ]);
        } else {
            return $this->failure_message('No Data Found!!');
        }
    }"""
    new_family = """    public function family_info()
    {
        $family = auth()->user()->families;
        if (!$family) {
            return $this->failure_message('No Data Found!!');
        }

        $data = (new FamilyInformation($family))->toArray(request());
        unset($data['father'], $data['mother']);
        return response()->json(['result' => true, 'data' => $data]);
    }"""
    source = replace_once(source, old_family, new_family, "legacy family response")

    old_family_update = """    public function family_info_update(Request $request)
    {
        $family = Family::where('user_id', auth()->id())->first();
        if (empty($family)) {
            $family           = new Family;
            $family->user_id  = auth()->id();
        }
        $family->father    = $request->father;
        $family->mother    = $request->mother;
        $family->sibling   = $request->sibling;
        $family->save();
        return $this->success_message('Family Info has been updated successfully');
    }"""
    new_family_update = """    public function family_info_update(Request $request)
    {
        $family = Family::where('user_id', auth()->id())->first();
        if (empty($family)) {
            $family = new Family;
            $family->user_id = auth()->id();
        }

        // Father and mother names are retired and are no longer accepted.
        if ($request->exists('sibling')) {
            $family->sibling = $request->input('sibling');
        }

        $family->save();
        return $this->success_message('Family Info has been updated successfully');
    }"""
    source = replace_once(source, old_family_update, new_family_update, "legacy family update")

    if "$lifestyle->diet" in source or "$family->father" in source or "$family->mother" in source:
        raise RuntimeError("Retired diet/father/mother writes remain in ProfileController")
    if "unset($data['diet'])" not in source:
        raise RuntimeError("Legacy lifestyle endpoint still returns diet")
    if "unset($data['father'], $data['mother'])" not in source:
        raise RuntimeError("Legacy family endpoint still returns parent names")
    return source


def patch_public_profile(source: str) -> str:
    start = source.find("    public function public_profile($id)")
    end = source.find("    public static function sendFirebaseNotification", start)
    if start < 0 or end < 0:
        raise RuntimeError("Could not isolate ProfileController::public_profile")
    method = source[start:end]

    method = replace_once(
        method,
        "            $data['basic_info'] = new BasicInformation($user);",
        """            // Public cards must never expose private contact fields.
            // Keep BasicInformation unchanged for the owner's private profile editor.
            $publicBasicInfo = (new BasicInformation($user))->toArray(request());
            unset($publicBasicInfo['phone'], $publicBasicInfo['email']);
            $data['basic_info'] = $publicBasicInfo;""",
        "public basic info privacy",
    )

    for line in [
        "            $data['contact_details']['email'] = $user->email;\n",
        "            $data['contact_details']['phone'] = $user->phone;\n",
    ]:
        method = method.replace(line, "")

    method = replace_once(
        method,
        "            $data['lifestyles'] = $user->lifestyles ? new LifeStyleResource($user->lifestyles) : null;",
        """            if ($user->lifestyles) {
                $publicLifestyle = (new LifeStyleResource($user->lifestyles))->toArray(request());
                unset($publicLifestyle['diet']);
                $data['lifestyles'] = $publicLifestyle;
            } else {
                $data['lifestyles'] = null;
            }""",
        "public lifestyle privacy",
    )

    method = method.replace(
        "            $data['families_information'] = $user->families ? new FamilyInformation($user->families) : null;\n",
        "",
    )
    method = replace_once(
        method,
        "            return $this->response_data($data);",
        "            unset($data['contact_details']);\n            return $this->response_data($data);",
        "public response final contact guard",
    )

    if re.search(r"\$data\[['\"]contact_details['\"]\]\s*=", method):
        raise RuntimeError("Public profile still constructs contact_details")
    if "$user->email" in method or "$user->phone" in method:
        raise RuntimeError("Public profile still reads email or phone")
    if "families_information" in method or "'diet'" not in method:
        raise RuntimeError("Public profile privacy exclusions did not apply")
    if "unset($publicLifestyle['diet'])" not in method:
        raise RuntimeError("Public lifestyle diet field is not excluded")

    return source[:start] + method + source[end:]


def patch_partner_update(source: str) -> str:
    start = source.find("    public function partner_expectation_update(")
    end = source.find("    public function password_update(", start)
    if start < 0 or end < 0:
        raise RuntimeError("Could not isolate partner_expectation_update")
    method = source[start:end]
    method = replace_once(
        method,
        "            'complexion' => ['partner_complexion', 'pertner_complexion', 'complexion'],",
        """            'complexion' => ['partner_complexion', 'pertner_complexion', 'complexion'],
            'eye_color' => ['partner_eye_color'],
            'hair_color' => ['partner_hair_color'],""",
        "partner appearance field mapping",
    )
    if "'eye_color' => ['partner_eye_color']" not in method or "'hair_color' => ['partner_hair_color']" not in method:
        raise RuntimeError("Partner eye/hair update mapping is incomplete")
    return source[:start] + method + source[end:]


def patch_partner_request(source: str) -> str:
    source = replace_once(
        source,
        "            'partner_profession' => ['sometimes', 'nullable', 'string', 'max:255'],",
        """            'partner_profession' => ['sometimes', 'nullable', 'string', 'max:255'],
            'partner_eye_color' => ['sometimes', 'nullable', 'string', 'max:50'],
            'partner_hair_color' => ['sometimes', 'nullable', 'string', 'max:50'],""",
        "partner eye/hair validation",
    )
    for field in ("partner_eye_color", "partner_hair_color"):
        if not re.search(r"['\"]" + field + r"['\"]\s*=>\s*\['sometimes',\s*'nullable',\s*'string',\s*'max:50'\]", source):
            raise RuntimeError(f"Missing validation rule for {field}")
    return source


def patch_partner_resource(source: str) -> str:
    source = replace_once(
        source,
        "            'preferred_country_id'  => $this->preferred_country_id,\n            'partner_country_id'    => $this->preferred_country_id,\n            'preferred_state_id'    => $this->preferred_state_id,\n            'partner_state_id'      => $this->preferred_state_id,",
        """            'preferred_country_id'  => $this->preferred_country_id,
            'partner_country_id'    => $this->preferred_country_id,
            'preferred_country_name' => $preferred_country ? $preferred_country->name : '',
            'partner_country_name'   => $preferred_country ? $preferred_country->name : '',
            'preferred_state_id'    => $this->preferred_state_id,
            'partner_state_id'      => $this->preferred_state_id,
            'preferred_state_name'  => $preferred_state ? $preferred_state->name : '',
            'partner_state_name'    => $preferred_state ? $preferred_state->name : '',
            'eye_color'             => $this->eye_color ?? '',
            'hair_color'            => $this->hair_color ?? '',""",
        "partner expectation display values",
    )
    for field in ("preferred_country_name", "preferred_state_name", "eye_color", "hair_color"):
        if f"'{field}'" not in source:
            raise RuntimeError(f"Partner expectation resource missing {field}")
    return source


def migration_text() -> str:
    return """<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('partner_expectations')) {
            throw new RuntimeException('The partner_expectations table does not exist.');
        }

        if (!Schema::hasColumn('partner_expectations', 'eye_color')) {
            Schema::table('partner_expectations', function (Blueprint $table) {
                $table->string('eye_color', 50)->nullable();
            });
        }

        if (!Schema::hasColumn('partner_expectations', 'hair_color')) {
            Schema::table('partner_expectations', function (Blueprint $table) {
                $table->string('hair_color', 50)->nullable();
            });
        }
    }

    public function down(): void
    {
        // Intentionally retain these nullable preference values on rollback.
        // Dropping columns here would destroy user data after a rollback.
    }
};
"""


def main() -> None:
    if not SOURCE.is_dir():
        raise SystemExit("Validated اصلاحات 3/Laravel baseline is missing; run the v3 preparation first.")

    shutil.rmtree(OUT, ignore_errors=True)
    shutil.copytree(SOURCE, LARAVEL)

    controller_path = LARAVEL / "app/Http/Controllers/Api/ProfileController.php"
    request_path = LARAVEL / "app/Http/Requests/PartnerExpectationRequest.php"
    resource_path = LARAVEL / "app/Http/Resources/PublicProfile/PartnerExpectationResource.php"

    controller = controller_path.read_text(encoding="utf-8")
    controller = patch_legacy_fields(controller)
    controller = patch_private_profile(controller)
    controller = patch_public_profile(controller)
    controller = patch_partner_update(controller)
    controller_path.write_text(controller, encoding="utf-8")

    request = request_path.read_text(encoding="utf-8")
    request_path.write_text(patch_partner_request(request), encoding="utf-8")

    resource = resource_path.read_text(encoding="utf-8")
    resource_path.write_text(patch_partner_resource(resource), encoding="utf-8")

    migration_path = LARAVEL / "database/migrations/2026_11_03_000000_add_partner_appearance_preferences.php"
    migration_path.parent.mkdir(parents=True, exist_ok=True)
    migration_path.write_text(migration_text(), encoding="utf-8")

    manifest = sorted(
        item.relative_to(LARAVEL).as_posix()
        for item in LARAVEL.rglob("*.php")
    )
    (OUT / "manifest.json").write_text(
        json.dumps({"version": 4, "type": "Laravel API replacement patch", "files": manifest},
                   ensure_ascii=False, indent=2) + "\n",
        encoding="utf-8",
    )
    (OUT / "README.md").write_text("""# وصال — اصلاحات 4 (Laravel API فقط)

هذه الحزمة تكمل إصلاحات الربط الخلفي لبطاقة العضو وتفضيلات الشريك. لا تحتوي ملفات Flutter ولا نسخة كاملة من أرشيف الاستضافة.

## ما الذي تصلحه؟
- تمنع إرسال البريد الإلكتروني ورقم الهاتف في استجابة بطاقة العضو العامة، بما في ذلك basic_info وcontact_details.
- تُبقي بيانات الاتصال في مسار الملف الشخصي الخاص full-my-profile حتى يستطيع صاحب الحساب تعديل بياناته.
- تحجب أسماء الأب والأم من استجابة الملف المجمّع والبطاقة العامة وواجهات family_info القديمة، وتوقف حفظ الاسمين من واجهة API القديمة. تحجب النظام الغذائي من استجابة الملف الخاص والعام وواجهة life_style القديمة، وتتجاهل أي محاولة قديمة لحفظه. تبقى بقية بيانات نمط الحياة (المشروبات وغيرها) تعمل. تبقى بيانات الاتصال في الملف المجمّع الخاص بصاحب الحساب فقط. لا تُحذف السجلات القديمة من قاعدة البيانات.
- تربط حفظ لون عيني ولون شعر الشريك مع التحقق من القيم وإرجاعهما ضمن توقعات الشريك.
- تُرجع أسماء البلد والمحافظة المفضّلين إضافة إلى المعرّفات حتى تعرض البطاقة أسماء مفهومة بدلاً من أرقام قاعدة البيانات.
- تضيف migration آمنة تضيف eye_color وhair_color إلى partner_expectations عند غيابهما فقط. لا تحذف أعمدة أو بيانات عند rollback.

## طريقة التركيب
1. خذ نسخة احتياطية من ملفات Laravel وقاعدة البيانات.
2. انسخ محتويات مجلد Laravel إلى جذر مشروع Laravel مع الحفاظ على المسارات.
3. نفّذ php artisan migrate --force.
4. اختبر بطاقة عضو آخر وتأكد من عدم وجود phone أو email أو بيانات العائلة أو diet في الاستجابة العامة.
5. اختبر حفظ ديانة/لغة/بلد/محافظة الشريك وألوان العين والشعر، ثم أعد فتح توقعات الشريك والبطاقة.

## حدود التحقق
اجتياز php -l يثبت سلامة صياغة PHP فقط. لا تعتبر هذه الحزمة منشورة أو مختبرة على قاعدة إنتاج؛ يجب اختبارها بعد تركيبها على بيئة اختبار أولاً.
""", encoding="utf-8")

    # Guard against accidentally packaging the unfiltered public route.
    final_controller = controller_path.read_text(encoding="utf-8")
    public_start = final_controller.find("    public function public_profile($id)")
    public_end = final_controller.find("    public static function sendFirebaseNotification", public_start)
    public_method = final_controller[public_start:public_end]
    if "$user->email" in public_method or "$user->phone" in public_method:
        raise RuntimeError("Privacy guard failed for public profile.")
    print(f"Prepared {OUT} with {len(manifest)} Laravel PHP files.")


if __name__ == "__main__":
    main()
