<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Controller;
use App\Http\Resources\MyProfileViewerResource;
use App\Models\ProfileViewer;
use App\Utility\NotificationUtility;
use App\Utility\SubscriptionUtility;
use App\Utility\MemberUtility;
use Illuminate\Http\Request;

class ProfileViewerController extends Controller
{
    /**
     * 🟢 1. جلب قائمة الأعضاء الذين زاروا بروفايل المستخدم الحالي
     * دعم الصلاحيات والسقف الديناميكي للباقة المفعلة فعلياً
     */
    public function my_profile_viewers()
    {
        $user = auth()->user();

        // 1. فحص جنس المستخدم (إناث / ذكور) بدقة لجميع حالات التخزين في قاعدة البيانات
        $genderStr = strtolower(trim($user->gender ?? ''));
        $memberGender = $user->member->gender ?? null;
        $isFemale = $genderStr == 'female' 
            || str_contains($genderStr, 'أنثى') 
            || str_contains($genderStr, 'انثى')
            || $memberGender == 2 
            || $memberGender == '2';

        // 2. فحص حالة الاشتراك النشط وتاريخ صلاحية الباقة
        $isSubscribed = SubscriptionUtility::hasActivePaidPackage($user);

        // 3. قراءة عدد الزوار المتاح في الباقة الحالية للعضو من جدول member
        $packageQuota = $isFemale
            ? -1
            : ($isSubscribed ? (int) ($user->member->remaining_profile_viewer_view ?? 0) : 0);

        // 4. تحديد سقف العرض الفعلي القادم من الباقة المفعلة:
        // - الإناث: مفتوح بلا حدود (9999)
        // - الذكور المشتركون: سقف الباقة المفعلة فعلياً (أو 50 كحد افتراضي للباقات)
        // - الذكور غير المشتركين (الحساب المجاني): 3 مشاهدات فقط
        if ($isFemale) {
            $allowedLimit = 9999;
        } elseif ($isSubscribed) {
            $allowedLimit = max(0, $packageQuota);
        } else {
            $allowedLimit = 3;
        }

        // 5. Enforce the three free profile visitors for non-subscribed male accounts
        // in the API response itself, not only by hiding rows inside Flutter.
        $viewersQuery = ProfileViewer::where('user_id', $user->id)
            ->whereIn("viewed_by", function ($query) {
                $query->select('id')
                    ->from('users')
                    ->where('approved', '1')
                    ->where('blocked', 0)
                    ->where('deactivated', 0)
                    ->where('permanently_delete', 0);
            });
        $totalViewers = (clone $viewersQuery)->count();

        if (!$isFemale && !$isSubscribed) {
            $profileViewers = (clone $viewersQuery)
                ->orderBy('updated_at', 'desc')
                ->limit(3)
                ->get();
            $lockedCount = max(0, $totalViewers - $profileViewers->count());
        } else {
            $profileViewers = (clone $viewersQuery)
                ->orderBy('updated_at', 'desc')
                ->paginate(15);
            $lockedCount = 0;
        }

        // 6. Return only entitled visitor records with the quota metadata.
        return MyProfileViewerResource::collection($profileViewers)->additional([
            'result' => true,
            'status' => 'success',
            'package_limit' => $allowedLimit,
            'remaining_quota' => $packageQuota,
            'is_subscribed' => $isSubscribed || $isFemale,
            'is_female' => $isFemale,
            'locked_count' => $lockedCount,
            'total_viewers' => $totalViewers,
        ]);
    }

    /**
     * 🟢 2. تسجيل زيارة جديدة لملف شخصي وإرسال إشعار فوري FCM للعضو المَزار
     */
    public function record_profile_view(Request $request)
    {
        $request->validate(['user_id' => ['required', 'integer', 'exists:users,id']]);
        $viewerId = (int) auth()->id();
        $targetUserId = (int) $request->user_id;
        if ($viewerId === $targetUserId) {
            return response()->json(['result' => false, 'message' => 'Cannot record self profile view'], 200);
        }

        $visit = SubscriptionUtility::withQuota(
            $targetUserId,
            'remaining_profile_viewer_view',
            function ($target, $targetMember) use ($viewerId) {
                $existing = ProfileViewer::where('user_id', $target->id)
                    ->where('viewed_by', $viewerId)
                    ->first();
                if ($existing) {
                    return null;
                }
                $record = ProfileViewer::create([
                    'user_id' => $target->id,
                    'viewed_by' => $viewerId,
                ]);
                return $record;
            }
        );

        if (!$visit) {
            return response()->json([
                'result' => false,
                'status' => 'quota_unavailable',
                'message' => 'The profile owner has no active paid plan or remaining profile-view quota.',
            ], 200);
        }

        try {
            $viewerUser = auth()->user();
            $viewerName = trim(($viewerUser->first_name ?? '') . ' ' . ($viewerUser->last_name ?? '')) ?: ($viewerUser->name ?? 'عضو في وصال');
            NotificationUtility::send_notification(
                $targetUserId,
                'profile_view',
                "قام $viewerName بزيارة ملفك الشخصي 👁️",
                "$viewerName viewed your profile 👁️",
                [
                    'type' => 'profile_view',
                    'viewer_id' => $viewerId,
                    'viewer_name' => $viewerName,
                    'viewer_photo' => $viewerUser->photo ?? '',
                ]
            );
        } catch (\Throwable $exception) {
            \Log::error('Profile view notification error: ' . $exception->getMessage());
        }

        return response()->json([
            'result' => true,
            'status' => 'success',
            'is_new_visit' => true,
            'message' => 'Profile view recorded successfully',
        ], 200);
    }
}
