<?php

namespace App\Console\Commands;

use App\Utility\SubscriptionUtility;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\Services\SubscriptionNotificationService;
use Illuminate\Console\Command;

class ExpireMemberPackages extends Command
{
    protected $signature = 'wesal:expire-member-packages';

    protected $description = 'Deactivate expired, disabled, deleted or otherwise invalid paid member packages';

    public function handle()
    {
        $count = SubscriptionUtility::deactivateInvalidSubscriptions();
        $notified = 0;

        // Expiration can also be reconciled by API reads. The event table acts as
        // an outbox so the notification is still delivered on the next scheduler run.
        if (\Illuminate\Support\Facades\Schema::hasTable('subscription_events')) {
            DB::table('subscription_events')
                ->whereIn('event_type', ['subscription_expired', 'subscription_invalidated'])
                ->whereNull('notified_at')
                ->orderBy('id')
                ->chunkById(100, function ($events) use (&$notified) {
                    foreach ($events as $event) {
                        $user = User::find($event->user_id);
                        if (!$user) {
                            DB::table('subscription_events')->where('id', $event->id)
                                ->whereNull('notified_at')->update([
                                    'notified_at' => now(),
                                    'push_notified_at' => now(),
                                    'updated_at' => now(),
                                ]);
                            continue;
                        }

                        $message = $event->event_type === 'subscription_expired'
                            ? 'انتهت صلاحية اشتراكك بتاريخ ' . $event->subscription_expires_at . '. عادت عضويتك إلى الوضع المجاني وتوقفت ميزات الباقة المدفوعة.'
                            : 'لم تعد باقتك الحالية صالحة أو مفعّلة. أُعيد حسابك إلى العضوية المجانية وتوقفت ميزات الباقة المدفوعة.';
                        $title = $event->event_type === 'subscription_expired' ? 'انتهاء الاشتراك' : 'إيقاف الاشتراك';
                        $sent = SubscriptionNotificationService::sendInApp(
                            $user,
                            $event->event_type,
                            $title,
                            $message,
                            $event->package_id
                        );

                        if ($sent) {
                            DB::table('subscription_events')->where('id', $event->id)
                                ->whereNull('notified_at')->update([
                                    'notified_at' => now(),
                                    'updated_at' => now(),
                                ]);
                            $notified++;
                        }
                    }
                }, 'id');
        }

        $pushNotified = 0;
        if (get_setting('firebase_push_notification') == 1 && \Illuminate\Support\Facades\Schema::hasTable('subscription_events')) {
            DB::table('subscription_events')
                ->whereIn('event_type', ['subscription_expired', 'subscription_invalidated'])
                ->whereNotNull('notified_at')
                ->whereNull('push_notified_at')
                ->orderBy('id')
                ->chunkById(100, function ($events) use (&$pushNotified) {
                    foreach ($events as $event) {
                        $user = User::find($event->user_id);
                        if (!$user) {
                            DB::table('subscription_events')->where('id', $event->id)
                                ->whereNull('push_notified_at')->update(['push_notified_at' => now(), 'updated_at' => now()]);
                            continue;
                        }
                        if (empty($user->fcm_token)) {
                            continue;
                        }
                        $expired = $event->event_type === 'subscription_expired';
                        $title = $expired ? 'انتهاء الاشتراك' : 'إيقاف الاشتراك';
                        $message = $expired
                            ? 'انتهت صلاحية اشتراكك بتاريخ ' . $event->subscription_expires_at . '. عادت عضويتك إلى الوضع المجاني وتوقفت ميزات الباقة المدفوعة.'
                            : 'لم تعد باقتك الحالية صالحة أو مفعّلة. أُعيد حسابك إلى العضوية المجانية وتوقفت ميزات الباقة المدفوعة.';
                        $sent = SubscriptionNotificationService::sendPush($user, $event->event_type, $title, $message, $event->package_id);
                        if ($sent) {
                            DB::table('subscription_events')->where('id', $event->id)
                                ->whereNull('push_notified_at')->update(['push_notified_at' => now(), 'updated_at' => now()]);
                            $pushNotified++;
                        }
                    }
                }, 'id');
        }

        $this->info("Deactivated {$count} invalid subscriptions; in-app notices: {$notified}; push deliveries: {$pushNotified}.");
        return self::SUCCESS;
    }
}
