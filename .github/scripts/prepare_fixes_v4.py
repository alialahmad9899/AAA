#!/usr/bin/env python3
from pathlib import Path
import json, re, shutil, tempfile, zipfile

ROOT = Path(".")
SOURCE = ROOT / "اصلاحات 3"
OUT = ROOT / "اصلاحات 4"
ARCHIVE = "ملفات الاتسضافة.zip"

STRIPE = r'''<?php
namespace App\Http\Controllers\Api\Payment;

use App\Http\Controllers\Api\Controller;
use App\Http\Controllers\Api\PackageController;
use App\Models\Member;
use App\Models\Package;
use App\Models\PackagePayment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\PaymentIntent;
use Stripe\Stripe;

class StripeController extends Controller
{
    public function create_payment_intent(Request $request)
    {
        $data = $request->validate(['package_id' => 'required|integer|min:1']);
        $user = auth('sanctum')->user();
        if (!$user) return response()->json(['result' => false, 'message' => 'Unauthenticated'], 401);

        $package = Package::find($data['package_id']);
        if (!$package) return response()->json(['result' => false, 'message' => 'الباقة المطلوبة غير موجودة.'], 404);

        $secret = config('services.stripe.secret') ?: env('STRIPE_SECRET');
        $publishable = config('services.stripe.key') ?: env('STRIPE_KEY');
        if (!$secret || !$publishable) return response()->json(['result' => false, 'message' => 'بوابة الدفع غير مهيأة على السيرفر.'], 503);

        $cents = (int) round(((float) $package->price) * 100);
        if ($cents < 50) return response()->json(['result' => false, 'message' => 'قيمة الباقة غير صالحة للدفع بالبطاقة.'], 422);

        try {
            Stripe::setApiKey($secret);
            $intent = PaymentIntent::create([
                'amount' => $cents,
                'currency' => 'usd',
                'payment_method_types' => ['card'],
                'metadata' => ['user_id' => (string) $user->id, 'package_id' => (string) $package->id],
            ]);
            return response()->json([
                'result' => true,
                'client_secret' => $intent->client_secret,
                'payment_intent_id' => $intent->id,
                'publishable_key' => $publishable,
                'amount' => $cents,
                'currency' => 'usd',
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Wesal Stripe PaymentIntent creation failed', [
                'user_id' => $user->id, 'package_id' => $package->id,
                'exception' => get_class($e), 'message' => $e->getMessage(),
            ]);
            return response()->json(['result' => false, 'message' => 'تعذر إنشاء عملية الدفع. يرجى المحاولة لاحقاً.'], 502);
        }
    }

    public function confirm_payment_intent(Request $request)
    {
        $data = $request->validate([
            'package_id' => 'required|integer|min:1',
            'payment_intent_id' => ['required', 'string', 'regex:/^pi_[A-Za-z0-9]+$/', 'max:100'],
        ]);
        $user = auth('sanctum')->user();
        if (!$user) return response()->json(['result' => false, 'message' => 'Unauthenticated'], 401);

        $package = Package::find($data['package_id']);
        if (!$package) return response()->json(['result' => false, 'message' => 'الباقة المطلوبة غير موجودة.'], 404);
        $secret = config('services.stripe.secret') ?: env('STRIPE_SECRET');
        if (!$secret) return response()->json(['result' => false, 'message' => 'بوابة الدفع غير مهيأة على السيرفر.'], 503);

        $intentId = $data['payment_intent_id'];
        try {
            Stripe::setApiKey($secret);
            $intent = PaymentIntent::retrieve($intentId);
            $expected = (int) round(((float) $package->price) * 100);
            $received = (int) ($intent->amount_received ?? 0);
            $currency = strtolower((string) ($intent->currency ?? ''));
            $ownerId = (string) ($intent->metadata->user_id ?? '');
            $metadataPackage = (string) ($intent->metadata->package_id ?? '');

            if (($intent->status ?? '') !== 'succeeded' ||
                $ownerId !== (string) $user->id ||
                $metadataPackage !== (string) $package->id ||
                $currency !== 'usd' || $received !== $expected) {
                Log::warning('Wesal Stripe PaymentIntent verification rejected', [
                    'user_id' => $user->id, 'package_id' => $package->id,
                    'payment_intent_id' => $intentId, 'stripe_status' => $intent->status ?? null,
                    'amount_received' => $received, 'expected_amount' => $expected, 'currency' => $currency,
                ]);
                return response()->json(['result' => false, 'message' => 'لم يتم تأكيد الدفع لدى Stripe أو أن بيانات العملية لا تطابق حسابك وباقتك.'], 422);
            }

            $already = DB::transaction(function () use ($user, $package, $intentId, $expected, $request) {
                User::whereKey($user->id)->lockForUpdate()->firstOrFail();
                $found = PackagePayment::query()
                    ->where('user_id', $user->id)
                    ->where('payment_method', 'stripe')
                    ->where('payment_details', 'like', '%' . $intentId . '%')
                    ->exists();
                if ($found) return true;
                if (!Member::where('user_id', $user->id)->exists()) {
                    throw new \RuntimeException('Member profile is missing for Stripe purchase.');
                }

                $details = json_encode([
                    'status' => 'Success', 'gateway' => 'stripe',
                    'payment_intent_id' => $intentId, 'currency' => 'usd',
                    'amount_received' => $expected,
                ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

                $activation = (new PackageController())->package_payment_done(
                    $user->id,
                    ['package_id' => $package->id, 'payment_method' => 'stripe', 'amount' => $package->price],
                    $details,
                    (string) $request->input('lang', 'ar')
                );
                $payload = $activation->getData(true);
                if (($payload['result'] ?? false) !== true) {
                    throw new \RuntimeException('Package activation did not return success.');
                }
                return false;
            });

            return response()->json([
                'result' => true,
                'already_processed' => $already,
                'message' => $already ? 'تم تأكيد هذه العملية وتفعيل الباقة مسبقاً.' : 'تم تأكيد الدفع وتفعيل الباقة بنجاح.',
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Wesal Stripe PaymentIntent confirmation failed', [
                'user_id' => $user->id, 'package_id' => $package->id,
                'payment_intent_id' => $intentId, 'exception' => get_class($e),
                'message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine(),
            ]);
            return response()->json(['result' => false, 'message' => 'تعذر تأكيد الدفع وتفعيل الباقة. لا تعاود الدفع قبل مراجعة حالة العملية مع الدعم.'], 502);
        }
    }

    public function success(Request $request)
    {
        if (!$request->filled('payment_intent_id') && $request->filled('payment_intent')) {
            $request->merge(['payment_intent_id' => $request->input('payment_intent')]);
        }
        return $this->confirm_payment_intent($request);
    }
}
'''

def unique_entry(names, suffix):
    found = [n for n in names if n.endswith(suffix)]
    if len(found) != 1:
        raise RuntimeError(f"{suffix}: expected 1 archive entry, got {len(found)}")
    return found[0]

def patch_auth(text):
    text, n = re.subn(
        r"(?m)^\s*\$autoVerify\s*=.*?;\s*$",
        "            // Email/OTP verification is disabled for Wesal registration.\n            $autoVerify = true;",
        text, count=1
    )
    if n != 1 and "$autoVerify = true;" not in text:
        raise RuntimeError("Could not patch signup verification setting")

    old_log = r"\Log::error('API signup transaction failed: ' . $e->getMessage());"
    if old_log in text:
        text = text.replace(old_log, r"""\Log::error('API signup transaction failed', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);""", 1)
    elif "API signup transaction failed', [" not in text:
        raise RuntimeError("Signup transaction log anchor missing")

    text = text.replace(
        "if (addon_activation('otp_system') && $request->phone != null && get_setting('registration_verification') != 1)",
        "if (!$autoVerify && addon_activation('otp_system') && $request->phone != null && get_setting('registration_verification') != 1)",
        1
    )
    member_line = "                    'on_behalves_id' => $request->input('on_behalf') ?? $request->input('on_behalves_id'),\n                    'user_id' => $user->id,"
    member_new = "                    'on_behalves_id' => $request->input('on_behalf') ?? $request->input('on_behalves_id'),\n                    'remaining_profile_viewer_view' => (int) ($package->profile_viewers_view ?? 0),\n                    'user_id' => $user->id,"
    if member_line in text:
        text = text.replace(member_line, member_new, 1)
    elif member_new not in text:
        raise RuntimeError("Signup member quota field was not inserted")

    logout = r"""public function logout(Request $request)
    {
        $user = auth()->user();
        if ($user) {
            $currentToken = $user->currentAccessToken();
            if ($currentToken) {
                $user->tokens()->where('id', $currentToken->id)->delete();
            }
            $deviceToken = trim((string) $request->input('device_token', ''));
            $storedToken = (string) ($user->fcm_token ?? '');
            if ($deviceToken !== '' && $storedToken !== '' && hash_equals($storedToken, $deviceToken)) {
                $user->fcm_token = null;
                $user->save();
            }
        }
        return $this->success_message('Successfully logged out');
    }"""
    text, n = re.subn(r"public function logout\(Request \$request\)\s*\{.*?\n    \}", lambda _: logout, text, count=1, flags=re.S)
    if n != 1:
        raise RuntimeError("Could not replace AuthController logout method")
    return text

def patch_routes(text):
    pairs = [
        ("Route::any('/stripe/success', 'StripeController@success')->name('api.stripe.success');",
         "Route::any('/stripe/success', 'StripeController@success')->name('api.stripe.success')->middleware('auth:sanctum');"),
        ("Route::post('/stripe/create-payment-intent', 'StripeController@create_payment_intent')->name('api.stripe.create_payment_intent');",
         "Route::post('/stripe/create-payment-intent', 'StripeController@create_payment_intent')->name('api.stripe.create_payment_intent')->middleware('auth:sanctum');"),
    ]
    for old, new in pairs:
        if old in text:
            text = text.replace(old, new, 1)
        elif new not in text:
            raise RuntimeError("Required Stripe route not found: " + old)
    confirmation = "Route::post('/stripe/confirm-payment-intent', 'StripeController@confirm_payment_intent')->name('api.stripe.confirm_payment_intent')->middleware('auth:sanctum');"
    if confirmation not in text:
        anchor = pairs[1][1]
        if anchor not in text: raise RuntimeError("Stripe route insertion anchor missing")
        text = text.replace(anchor, anchor + "\n        " + confirmation, 1)
    if "Route::get('/full-my-profile', 'ProfileController@get_full_my_profile');" not in text:
        raise RuntimeError("Unified full-my-profile route is missing")
    return text

def patch_profile(text):
    pattern = r"if \(\$user->id != \$auth_user->id\) \{\s*\$profileViewed = ProfileViewer::where\('user_id', \$user->id\)->where\('viewed_by', \$auth_user->id\)->first\(\);.*?\n            \}"
    replacement = r"""if ($auth_user && $user->id != $auth_user->id) {
                $profileViewed = ProfileViewer::firstOrNew([
                    'user_id' => $user->id,
                    'viewed_by' => $auth_user->id,
                ]);
                $isNewVisit = !$profileViewed->exists;
                // Do not consume the profile owner's quota for seeing their own visitors.
                $profileViewed->updated_at = now();
                $profileViewed->save();

                if ($isNewVisit) {
                    try {
                        $notify_type = 'profile_viewed';
                        $id = unique_notify_id();
                        $notify_by = $auth_user->id;
                        $info_id = $user->id;
                        $message = $auth_user->first_name . ' ' . $auth_user->last_name . ' ' . translate(' has viewed your profile.');
                        $route = route('member_profile', $auth_user->id);
                        if (get_setting('firebase_push_notification') == 1) {
                            $fcmTokens = User::where('id', $user->id)->whereNotNull('fcm_token')->pluck('fcm_token')->toArray();
                            self::sendFirebaseNotification($fcmTokens, $user, $notify_type, $message, $notify_by);
                        }
                        Notification::send($user, new DbStoreNotification($notify_type, $id, $notify_by, $info_id, $message, $route));
                    } catch (\Exception $e) {
                        \Log::warning('Profile view notification failed: ' . $e->getMessage());
                    }
                }
            }"""
    result, n = re.subn(pattern, lambda _: replacement, text, count=1, flags=re.S)
    if n != 1 and "Do not consume the profile owner's quota" not in text:
        raise RuntimeError("Profile visit block not found for safe update")
    return result if n else text

def patch_hosting(path):
    with zipfile.ZipFile(path, "r") as src:
        names = src.namelist()
        auth_path = unique_entry(names, "app/Http/Controllers/Api/AuthController.php")
        routes_path = unique_entry(names, "routes/api.php")
        profile_path = unique_entry(names, "app/Http/Controllers/Api/ProfileController.php")
        stripe_path = unique_entry(names, "app/Http/Controllers/Api/Payment/StripeController.php")
        replacements = {
            auth_path: patch_auth(src.read(auth_path).decode()).encode(),
            routes_path: patch_routes(src.read(routes_path).decode()).encode(),
            profile_path: patch_profile(src.read(profile_path).decode()).encode(),
            stripe_path: STRIPE.encode(),
        }
        temp = path.with_suffix(".v4.tmp.zip")
        try:
            with zipfile.ZipFile(temp, "w", zipfile.ZIP_DEFLATED, compresslevel=9) as dst:
                for info in src.infolist():
                    dst.writestr(info, replacements.get(info.filename, src.read(info.filename)))
            with zipfile.ZipFile(temp) as check:
                bad = check.testzip()
                if bad: raise RuntimeError("Hosting ZIP CRC issue: " + bad)
            temp.replace(path)
        finally:
            if temp.exists(): temp.unlink()

def main():
    if not (SOURCE / "README.md").is_file():
        raise SystemExit("Validated اصلاحات 3 baseline missing; run V3 build first.")
    shutil.rmtree(OUT, ignore_errors=True)
    shutil.copytree(SOURCE, OUT)
    hostzip = OUT / "Hosting" / ARCHIVE
    if not hostzip.is_file(): raise RuntimeError("Hosting archive missing from V3")

    patch_hosting(hostzip)
    with zipfile.ZipFile(hostzip) as z:
        exports = {
            "Laravel/app/Http/Controllers/Api/AuthController.php": z.read(unique_entry(z.namelist(), "app/Http/Controllers/Api/AuthController.php")),
            "Laravel/routes/api.php": z.read(unique_entry(z.namelist(), "routes/api.php")),
            "Laravel/app/Http/Controllers/Api/Payment/StripeController.php": z.read(unique_entry(z.namelist(), "app/Http/Controllers/Api/Payment/StripeController.php")),
            "Laravel/app/Http/Controllers/Api/ProfileController.php": z.read(unique_entry(z.namelist(), "app/Http/Controllers/Api/ProfileController.php")),
            "Laravel/app/Http/Controllers/Api/ProfileViewerController.php": z.read(unique_entry(z.namelist(), "app/Http/Controllers/Api/ProfileViewerController.php")),
        }
    for rel, data in exports.items():
        target = OUT / rel
        target.parent.mkdir(parents=True, exist_ok=True)
        target.write_bytes(data)

    flutter = [
        "lib/services/api_service.dart",
        "lib/screens/home/tabs/account_tab.dart",
        "lib/screens/my_interests_screen.dart",
        "lib/screens/my_profile_viewers_screen.dart",
        "lib/screens/chat_detail_screen.dart",
    ]
    for rel in flutter:
        source = ROOT / rel
        if not source.is_file(): raise RuntimeError("Missing updated Flutter file: " + rel)
        target = OUT / "Flutter" / rel
        target.parent.mkdir(parents=True, exist_ok=True)
        shutil.copy2(source, target)

    manifest_file = OUT / "hosting-patch-manifest.json"
    raw = manifest_file.read_text(encoding="utf-8").strip()
    if raw.endswith("\\n"): raw = raw[:-2]
    manifest = json.loads(raw)
    manifest["version"] = 4
    manifest["release"] = "اصلاحات 4"
    manifest["changes"] = [
        "Stripe intents are authenticated and verified server-side against Stripe status, user, package, currency, and amount; activation is idempotent.",
        "Signup email/OTP verification is disabled; Laravel logs detailed signup exception diagnostics.",
        "New accounts initialize their own profile-visitor quota from the selected default package.",
        "Profile views are recorded without decrementing the visited user's allowance; first-visit notifications are retained.",
        "Logout revokes the current Sanctum token and unlinks FCM only when the supplied device token matches.",
        "Interest, profile visitors, chats, chat messages, and older messages distinguish API failures from successful empty results and provide retry UI.",
        "Includes all prior V3 profile, optional phone, image, and field compatibility fixes.",
    ]
    manifest["flutter_files"] = sorted(set(manifest.get("flutter_files", [])) | set(flutter))
    manifest["laravel_files"] = sorted(set(manifest.get("laravel_files", [])) | set(exports))
    manifest_file.write_text(json.dumps(manifest, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")

    (OUT / "README.md").write_text("""# إصلاحات 4 — Wesal

## الإصلاحات
- التسجيل: تعطيل تحقق البريد وOTP، وكتابة استثناء HTTP 500 بالتفصيل في Laravel logs دون كشفه للتطبيق. تهيئة حصة زوار الملف من الباقة الافتراضية عند إنشاء العضو.
- الملف الشخصي: يتضمن المسار الموحد full-my-profile مع التراجع إلى طلبات الأقسام، وإصلاحات الصورة والهاتف والحقول السابقة.
- Stripe: إنشاء الدفع وتأكيده يتطلبان Sanctum. يتحقق الخادم من PaymentIntent الحقيقي، حالة succeeded، مالك العملية، الباقة، العملة والمبلغ. يمنع تفعيل نفس PaymentIntent أكثر من مرة.
- الدفع اليدوي: يبقى طلباً بانتظار مراجعة الإدارة، وليس تفعيل اشتراك فوري.
- الاهتمامات والمحادثات والزوار: أخطاء الاتصال لا تظهر كقوائم فارغة؛ تتوفر حالات خطأ وإعادة محاولة.
- الزيارات: لا تستهلك زيارة الملف حصة صاحب الملف لمشاهدة زواره؛ يُحدّث وقت آخر زيارة ويُرسل إشعار عند أول زيارة فقط.
- الخروج: إلغاء Sanctum token ومسح FCM من الحساب فقط إذا طابق رمز الجهاز الذي خرج، لمنع فصل جهاز آخر.

## النشر
1. خذ نسخة احتياطية من الاستضافة وقاعدة البيانات.
2. انسخ محتويات Laravel إلى جذر Laravel مع الحفاظ على المسارات، أو استخدم Hosting/ملفات الاتسضافة.zip ضمن آلية النشر المعتادة. Flutter لا يُرفع إلى الاستضافة.
3. نفّذ من جذر Laravel: php artisan route:clear ثم php artisan route:cache (إن كان route cache مستخدماً).
4. تحقق من المسارات باستخدام php artisan route:list --path=stripe وphp artisan route:list --path=member/full-my-profile.
5. انسخ Flutter إلى مشروع التطبيق ثم نفّذ flutter pub get وflutter analyze وflutter test وابنِ APK جديداً.
6. عند تجربة تسجيل جديدة، راقب storage/logs/laravel.log لتحديد استثناء التسجيل 500.
7. اختبر على staging بحسابين منفصلين: التسجيل والصورة، حفظ وتعديل الأقسام، الباقات، Stripe test mode، الدفع اليدوي المعلق، الإعجاب والاهتمامات، الزيارات، المحادثات والرسائل القديمة، ثم الخروج والتأكد من رفض توكن الجلسة القديمة.

## حدود التحقق
فحوصات CI تشمل Flutter analyze/test، صياغة ملفات PHP المتغيرة، وفحص سلامة أرشيف الاستضافة. لا تعادل اختباراً حياً على الإنتاج أو دفعاً حقيقياً؛ مفاتيح Stripe وقاعدة البيانات الفعلية تتطلب بيئة staging.
""", encoding="utf-8")

    archive = ROOT / "اصلاحات 4.zip"
    if archive.exists(): archive.unlink()
    with zipfile.ZipFile(archive, "w", zipfile.ZIP_DEFLATED, compresslevel=9) as z:
        for file in sorted(OUT.rglob("*")):
            if file.is_file(): z.write(file, file.relative_to(ROOT).as_posix())
    with zipfile.ZipFile(archive) as z:
        if z.testzip(): raise RuntimeError("V4 ZIP integrity failure")
    print("Prepared إصلاحات 4 successfully")

if __name__ == "__main__":
    main()
