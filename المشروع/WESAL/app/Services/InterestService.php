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
        $actorId = (int) auth()->id();
        if ($actorId <= 0 || !is_numeric($interest_id) || (int) $interest_id <= 0) {
            return false;
        }

        try {
            // Lock the incoming request and verify its owner before changing
            // its state or creating a conversation. The lock also serializes
            // simultaneous accept/reject requests for the same interest.
            $accepted = DB::transaction(function () use ($interest_id, $actorId) {
                $interest = ExpressInterest::whereKey((int) $interest_id)
                    ->where('user_id', $actorId)
                    ->lockForUpdate()
                    ->first();

                if (!$interest || (int) $interest->status !== 0) {
                    return null;
                }

                $interest->status = 1;
                $interest->save();

                $existingThread = ChatThread::where(function ($query) use ($interest) {
                    $query->where('sender_user_id', $interest->interested_by)
                        ->where('receiver_user_id', $interest->user_id);
                })->orWhere(function ($query) use ($interest) {
                    $query->where('sender_user_id', $interest->user_id)
                        ->where('receiver_user_id', $interest->interested_by);
                })->lockForUpdate()->first();

                if (!$existingThread) {
                    $thread = new ChatThread();
                    $thread->thread_code = $interest->interested_by . date('Ymd') . $interest->user_id;
                    $thread->sender_user_id = $interest->interested_by;
                    $thread->receiver_user_id = $interest->user_id;
                    $thread->save();
                }

                return [
                    'interest_id' => (int) $interest->id,
                    'interested_by' => (int) $interest->interested_by,
                ];
            });

            if (!$accepted) {
                return false;
            }

            // Notifications are sent after the transaction commits. Their
            // failure must not roll back the accepted request or chat thread.
            $notifyUser = User::find($accepted['interested_by']);
            $actor = User::find($actorId);
            if ($notifyUser && $actor) {
                $message = trim(($actor->first_name ?? '') . ' ' . ($actor->last_name ?? ''))
                    . ' ' . translate(' has accepted your interest.');
                $this->notifyUser(
                    $accepted['interested_by'],
                    $notifyUser,
                    'accept_interest',
                    $actorId,
                    $accepted['interest_id'],
                    $message,
                    route('my_interests.index')
                );

                if ($notifyUser->email != null && get_email_template('email_on_accepting_interest', 'status')) {
                    try {
                        EmailUtility::email_on_accept_request($notifyUser, 'email_on_accepting_interest');
                    } catch (\Throwable $exception) {
                        Log::error('Interest accept email failed.', ['message' => $exception->getMessage()]);
                    }
                }

                if ($notifyUser->phone != null && addon_activation('otp_system') && get_sms_template('accept_interest', 'status') == 1) {
                    try {
                        SmsUtility::sms_on_accept_request($notifyUser, 'accept_interest');
                    } catch (\Throwable $exception) {
                        Log::error('Interest accept SMS failed.', ['message' => $exception->getMessage()]);
                    }
                }
            }

            return true;
        } catch (\Throwable $exception) {
            Log::error('InterestService Accept Error.', ['message' => $exception->getMessage()]);
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
        $actorId = (int) auth()->id();
        if ($actorId <= 0 || !is_numeric($interest_id) || (int) $interest_id <= 0) {
            return false;
        }

        try {
            $rejected = DB::transaction(function () use ($interest_id, $actorId) {
                $interest = ExpressInterest::whereKey((int) $interest_id)
                    ->where('user_id', $actorId)
                    ->lockForUpdate()
                    ->first();

                // Only a still-pending incoming request can be rejected.
                if (!$interest || (int) $interest->status !== 0) {
                    return null;
                }

                $senderId = (int) $interest->interested_by;
                $requestId = (int) $interest->id;
                $interest->delete();

                return [
                    'interest_id' => $requestId,
                    'interested_by' => $senderId,
                ];
            });

            if (!$rejected) {
                return false;
            }

            $notifyUser = User::find($rejected['interested_by']);
            $actor = User::find($actorId);
            if ($notifyUser && $actor) {
                $message = trim(($actor->first_name ?? '') . ' ' . ($actor->last_name ?? ''))
                    . ' ' . translate(' has rejected your interest.');
                $this->notifyUser(
                    $rejected['interested_by'],
                    $notifyUser,
                    'reject_interest',
                    $actorId,
                    $rejected['interest_id'],
                    $message,
                    route('my_interests.index')
                );
            }

            return true;
        } catch (\Throwable $exception) {
            Log::error('InterestService Reject Error.', ['message' => $exception->getMessage()]);
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
            if (
                $notify_user
                && !empty($notify_user->fcm_token)
                && get_setting('firebase_push_notification') == 1
            ) {
                try {
                    $pushAccepted = FirebaseNotificationService::sendPushNotification(
                        $notify_user->fcm_token,
                        $title,
                        $message,
                        [
                            'type' => (string) $notify_type,
                            'notify_by' => (string) $notify_by,
                            'actor_id' => (string) $notify_by,
                            'user_id' => (string) $notify_by,
                            'info_id' => (string) $info_id,
                            'interest_id' => (string) $info_id,
                            'notification_id' => (string) $id,
                            'route' => (string) $route,
                        ]
                    );
                    if (!$pushAccepted) {
                        Log::warning('Interest notification persisted, but Firebase did not accept the push.', [
                            'receiver_id' => $user_id,
                            'notification_id' => $id,
                            'type' => $notify_type,
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error("❌ [Firebase Notification Error]: " . $e->getMessage());
                }
            } elseif ($notify_user) {
                Log::warning('Interest push notification skipped.', [
                    'receiver_id' => $user_id,
                    'type' => $notify_type,
                    'push_enabled' => (int) get_setting('firebase_push_notification') === 1,
                    'has_device_token' => !empty($notify_user->fcm_token),
                ]);
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