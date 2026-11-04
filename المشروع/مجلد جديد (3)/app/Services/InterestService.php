<?php

namespace App\Services;

use App\Models\ChatThread;
use App\Models\ExpressInterest;
use App\Models\User;
use App\Notifications\DbStoreNotification;
use App\Services\FirebaseNotificationService;
use App\Utility\EmailUtility;
use App\Utility\MemberUtility;
use App\Utility\SubscriptionUtility;
use Illuminate\Support\Facades\DB;
use App\Utility\SmsUtility;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * ============================================================================
 * اسم الملف: app/Services/InterestService.php
 * الوصف: خدمة إدارة طلبات الإعجاب، قبولها، رفضها، وربط المحادثات المباشرة والإشعارات
 * ============================================================================
 */
class InterestService
{
    /**
     * 🟢 1. إنشاء وحفظ طلب إعجاب جديد مع إرسال الإشعارات الآمنة
     * 
     * @param int $user_id معرّف العضو المستهدف بالاهتمام
     * @return bool نجاح أو فشل العملية
     */
    public function store($user_id)
    {
        try {
            $authenticatedUser = auth()->user();
            if (!$authenticatedUser || (int) $user_id === (int) $authenticatedUser->id) {
                return false;
            }

            $interestId = DB::transaction(function () use ($authenticatedUser, $user_id) {
                // Lock the sender row to serialize quota checks and avoid concurrent overuse.
                $sender = User::whereKey($authenticatedUser->id)->lockForUpdate()->first();
                if (!$sender) {
                    return null;
                }

                $member = \App\Models\Member::where('user_id', $sender->id)->lockForUpdate()->first();
                $isFemale = MemberUtility::is_female($sender->id);
                $hasPaidPlan = SubscriptionUtility::hasActivePaidPackage($sender->id);

                $alreadySent = ExpressInterest::where('user_id', $user_id)
                    ->where('interested_by', $sender->id)->exists();
                $alreadyReceived = ExpressInterest::where('user_id', $sender->id)
                    ->where('interested_by', $user_id)->exists();
                if ($alreadySent || $alreadyReceived) {
                    return null;
                }

                if (!$isFemale && !$hasPaidPlan) {
                    $freeCount = ExpressInterest::where('interested_by', $sender->id)->count();
                    if ($freeCount >= 5) {
                        return null;
                    }
                }

                if (!$isFemale && $hasPaidPlan) {
                    if (!$member) {
                        return null;
                    }
                    $quota = (int) $member->remaining_interest;
                    if ($quota !== -1 && $quota <= 0) {
                        return null;
                    }
                    if ($quota > 0) {
                        $member->remaining_interest = $quota - 1;
                        $member->save();
                    }
                }

                $interest = new ExpressInterest();
                $interest->user_id = $user_id;
                $interest->interested_by = $sender->id;
                $interest->save();

                return $interest->id;
            });

            if (!$interestId) {
                return false;
            }

            // Notifications and email are deliberately outside the quota transaction.
            $notifyUser = User::find($user_id);
            if ($notifyUser) {
                $notifyType = 'express_interest';
                $message = $authenticatedUser->first_name . ' ' . $authenticatedUser->last_name . ' ' . translate(' has Expressed Interest On You.');
                $this->notifyUser(
                    $user_id,
                    $notifyUser,
                    $notifyType,
                    $authenticatedUser->id,
                    $interestId,
                    $message,
                    route('interest_requests')
                );

                if ($notifyUser->email != null && get_email_template('email_on_express_interest', 'status')) {
                    try {
                        EmailUtility::email_on_request($notifyUser, 'email_on_express_interest');
                    } catch (\Throwable $exception) {
                        Log::error('Interest email failed.', ['message' => $exception->getMessage()]);
                    }
                }

                if ($notifyUser->phone != null && addon_activation('otp_system') && get_sms_template('express_interest', 'status') == 1) {
                    try {
                        SmsUtility::sms_on_request($notifyUser, 'express_interest');
                    } catch (\Throwable $exception) {
                        Log::error('Interest SMS failed.', ['message' => $exception->getMessage()]);
                    }
                }
            }

            return true;
        } catch (\Throwable $exception) {
            Log::error('InterestService Store Error.', ['message' => $exception->getMessage()]);
            return false;
        }
    }

    /**
     * 🟢 2. قبول طلب الإعجاب وفتح مسار محادثة تلقائياً
     * 
     * @param int $interest_id معرّف سجل الإعجاب
     * @return bool نجاح أو فشل التفعيل
     */
    public function accept($interest_id)
    {
        try {
            $interest = ExpressInterest::find($interest_id);
            if ($interest) {
                // تحديث حالة الطلب إلى مقبول (1)
                $interest->status = 1;
                $interest->save();

                // التحقق من عدم وجود محادثة سابقة بين الطرفين
                $existing_chat_thread = ChatThread::where(function ($query) use ($interest) {
                    $query->where('sender_user_id', $interest->interested_by)
                          ->where('receiver_user_id', $interest->user_id);
                })->orWhere(function ($query) use ($interest) {
                    $query->where('receiver_user_id', $interest->interested_by)
                          ->where('sender_user_id', $interest->user_id);
                })->first();

                // إن لم توجد محادثة، يتم إنشاؤها فورياً
                if ($existing_chat_thread == null) {
                    $chat_thread                   = new ChatThread();
                    $chat_thread->thread_code      = $interest->interested_by . date('Ymd') . $interest->user_id;
                    $chat_thread->sender_user_id   = $interest->interested_by;
                    $chat_thread->receiver_user_id = $interest->user_id;
                    $chat_thread->save();
                }

                // إشعار مُرسل الإعجاب الأصلي بالقبول
                $notify_user = User::find($interest->interested_by);
                if ($notify_user) {
                    $notify_type = 'accept_interest';
                    $notify_by   = auth()->user()->id;
                    $info_id     = $interest->id;
                    $message     = auth()->user()->first_name . ' ' . auth()->user()->last_name . ' ' . translate(' has accepted your interest.');
                    $route       = route('my_interests.index');

                    $this->notifyUser($interest->interested_by, $notify_user, $notify_type, $notify_by, $info_id, $message, $route);

                    if ($notify_user->email != null && get_email_template('email_on_accepting_interest', 'status')) {
                        try {
                            EmailUtility::email_on_accept_request($notify_user, 'email_on_accepting_interest');
                        } catch (\Exception $e) {
                            Log::error("❌ [Email Accept Send Error]: " . $e->getMessage());
                        }
                    }

                    if ($notify_user->phone != null && addon_activation('otp_system') && (get_sms_template('accept_interest', 'status') == 1)) {
                        try {
                            SmsUtility::sms_on_accept_request($notify_user, 'accept_interest');
                        } catch (\Exception $e) {
                            Log::error("❌ [SMS Accept Send Error]: " . $e->getMessage());
                        }
                    }
                }

                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error("❌ [InterestService Accept Error]: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 🟢 3. رفض طلب الإعجاب وحذفه
     * 
     * @param int $interest_id معرّف سجل الإعجاب
     * @return bool نجاح أو فشل الحذف
     */
    public function reject($interest_id)
    {
        try {
            $interest = ExpressInterest::find($interest_id);
            if ($interest) {
                $interested_by = $interest->interested_by;
                ExpressInterest::destroy($interest_id);

                $notify_user = User::find($interested_by);
                if ($notify_user) {
                    $notify_type = 'reject_interest';
                    $notify_by   = auth()->user()->id;
                    $info_id     = $interest_id;
                    $message     = auth()->user()->first_name . ' ' . auth()->user()->last_name . ' ' . translate(' has rejected your interest.');
                    $route       = route('my_interests.index');

                    $this->notifyUser($interested_by, $notify_user, $notify_type, $notify_by, $info_id, $message, $route);
                }

                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error("❌ [InterestService Reject Error]: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 🟢 4. إرسال الإشعارات الفورية وتخزينها في قاعدة البيانات وFirebase بأعلى كفاءة
     */
    public function notifyUser($user_id, $notify_user, $notify_type, $notify_by, $info_id, $message, $route)
    {
        try {
            $id    = unique_notify_id();
            $title = $this->getNotificationTitle($notify_type);

            // 1. إرسال إشعار Firebase Push
            if ($notify_user && !empty($notify_user->fcm_token)) {
                try {
                    FirebaseNotificationService::sendPushNotification(
                        $notify_user->fcm_token,
                        $title,
                        $message
                    );
                } catch (\Exception $e) {
                    Log::error("❌ [Firebase Notification Error]: " . $e->getMessage());
                }
            }

            // 2. حفظ الإشعار في قاعدة البيانات
            if ($notify_user) {
                try {
                    Notification::send($notify_user, new DbStoreNotification($notify_type, $id, $notify_by, $info_id, $message, $route, $title));
                } catch (\Exception $e) {
                    Log::error("❌ [DbStoreNotification Error]: " . $e->getMessage());
                }
            }

            return true;
        } catch (\Exception $e) {
            Log::error("❌ [InterestService NotifyUser Error]: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 🟢 5. جلب عنوان الإشعار بحسب نوعه
     */
    private function getNotificationTitle($type)
    {
        switch ($type) {
            case 'express_interest':
                return 'إعجاب جديد ✨';
            case 'accept_interest':
                return 'تم قبول الإعجاب 🎉';
            case 'reject_interest':
                return 'تحديث طلب الإعجاب';
            default:
                return 'إشعار جديد من وصال';
        }
    }
}