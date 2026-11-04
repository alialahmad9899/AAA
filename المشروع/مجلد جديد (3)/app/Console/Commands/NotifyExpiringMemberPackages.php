<?php

namespace App\Console\Commands;

use App\Models\Member;
use App\Models\User;
use App\Notifications\DbStoreNotification;
use App\Services\FirebaseNotificationService;
use App\Utility\SubscriptionUtility;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class NotifyExpiringMemberPackages extends Command
{
    protected $signature = 'wesal:notify-expiring-member-packages';

    protected $description = 'Send one in-app and push reminder one day before a paid package expires';

    public function handle(): int
    {
        $targetExpiry = now()->addDay()->toDateString();
        $sent = 0;

        // Start reminders at 09:00 local time. The command runs hourly so a transient
        // database failure at 09:00 can be retried later the same day without duplicates.
        if ((int) now()->format('G') >= 9) {
        Member::query()
            ->whereNotNull('current_package_id')
            ->whereDate('package_validity', $targetExpiry)
            ->orderBy('id')
            ->chunkById(100, function ($members) use ($targetExpiry, &$sent) {
                foreach ($members as $candidate) {
                    try {
                        $payload = DB::transaction(function () use ($candidate, $targetExpiry) {
                            $user = User::whereKey($candidate->user_id)->lockForUpdate()->first();
                            if (!$user) {
                                return null;
                            }
                            $member = Member::whereKey($candidate->id)->lockForUpdate()->first();
                            if (!$member || (int) $member->user_id !== (int) $user->id ||
                                empty($member->package_validity) ||
                                Carbon::parse($member->package_validity)->toDateString() !== $targetExpiry) {
                                return null;
                            }

                            $package = SubscriptionUtility::currentActivePaidPackageForMember($member);
                            if (!$package) {
                                return null;
                            }
                            // A valid paid package is authoritative; repair a legacy membership flag drift.
                            if ((int) $user->membership !== 2) {
                                $user->membership = 2;
                                $user->save();
                            }

                            // Unique index ensures that repeated/manual scheduler runs cannot duplicate reminders.
                            $inserted = DB::table('subscription_events')->insertOrIgnore([
                                'user_id' => $user->id,
                                'member_id' => $member->id,
                                'package_id' => $package->id,
                                'event_type' => 'expiry_reminder',
                                'subscription_expires_at' => $targetExpiry,
                                'actor_id' => null,
                                'details' => json_encode(['reminder_for' => $targetExpiry], JSON_UNESCAPED_UNICODE),
                                'notified_at' => null,
                                'push_notified_at' => null,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);

                            if ($inserted !== 1) {
                                return null;
                            }

                            $expiryLabel = \Carbon\Carbon::parse($targetExpiry)->format('Y-m-d');
                            $title = 'تذكير بانتهاء الاشتراك';
                            $message = 'تنتهي باقة ' . $package->name . ' غداً بتاريخ ' . $expiryLabel . '. جدّد اشتراكك قبل انتهائها للحفاظ على ميزات العضوية المميزة.';

                            // Persist the notification in the same transaction as the unique event record.
                            $user->notify(new DbStoreNotification(
                                'subscription_expiry_reminder',
                                (string) Str::uuid(),
                                null,
                                $package->id,
                                $message,
                                '',
                                $title
                            ));
                            DB::table('subscription_events')
                                ->where('member_id', $member->id)
                                ->where('event_type', 'expiry_reminder')
                                ->whereDate('subscription_expires_at', $targetExpiry)
                                ->whereNull('notified_at')
                                ->update(['notified_at' => now(), 'updated_at' => now()]);

                            return [
                                'user_id' => $user->id,
                                'member_id' => $member->id,
                                'token' => $user->fcm_token,
                                'package_id' => $package->id,
                                'title' => $title,
                                'message' => $message,
                            ];
                        });

                        if (!$payload) {
                            continue;
                        }

                        if (get_setting('firebase_push_notification') == 1 && !empty($payload['token'])) {
                            $pushSent = FirebaseNotificationService::sendPushNotification(
                                $payload['token'],
                                $payload['title'],
                                $payload['message'],
                                [
                                    'type' => 'subscription_expiry_reminder',
                                    'info_id' => $payload['package_id'],
                                    'route' => '',
                                ]
                            );
                            if ($pushSent) {
                                DB::table('subscription_events')
                                    ->where('member_id', $payload['member_id'])
                                    ->where('event_type', 'expiry_reminder')
                                    ->whereDate('subscription_expires_at', $targetExpiry)
                                    ->whereNull('push_notified_at')
                                    ->update(['push_notified_at' => now(), 'updated_at' => now()]);
                            } else {
                                Log::warning('Subscription reminder was saved in-app but FCM delivery failed; it will be retried by the scheduler.', [
                                    'user_id' => $payload['user_id'],
                                    'package_id' => $payload['package_id'],
                                ]);
                            }
                        }

                        $sent++;
                    } catch (\Throwable $exception) {
                        Log::error('Failed to create subscription expiry reminder.', [
                            'member_id' => $candidate->id,
                            'message' => $exception->getMessage(),
                        ]);
                    }
                }
            });
        }

        if (get_setting('firebase_push_notification') == 1) {
            DB::table('subscription_events')
                ->where('event_type', 'expiry_reminder')
                ->whereNotNull('notified_at')
                ->whereNull('push_notified_at')
                ->whereDate('subscription_expires_at', '>', now()->toDateString())
                ->where('created_at', '<=', now()->subMinutes(30))
                ->orderBy('id')
                ->chunkById(100, function ($events) use (&$sent) {
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
                        $member = Member::find($event->member_id);
                        $currentPackage = SubscriptionUtility::currentActivePaidPackageForMember($member);
                        if (!$currentPackage || (int) $currentPackage->id !== (int) $event->package_id ||
                            Carbon::parse($member->package_validity)->toDateString() !== Carbon::parse($event->subscription_expires_at)->toDateString()) {
                            DB::table('subscription_events')->where('id', $event->id)
                                ->whereNull('push_notified_at')->update(['push_notified_at' => now(), 'updated_at' => now()]);
                            continue;
                        }
                        $package = $currentPackage;
                        $packageName = $package ? $package->name : 'الاشتراك المميز';
                        $expiry = Carbon::parse($event->subscription_expires_at)->format('Y-m-d');
                        $pushSent = FirebaseNotificationService::sendPushNotification(
                            $user->fcm_token,
                            'تذكير بانتهاء الاشتراك',
                            'تنتهي باقة ' . $packageName . ' غداً بتاريخ ' . $expiry . '. جدّد اشتراكك قبل انتهائها للحفاظ على ميزات العضوية المميزة.',
                            ['type' => 'subscription_expiry_reminder', 'info_id' => $event->package_id, 'route' => '']
                        );
                        if ($pushSent) {
                            DB::table('subscription_events')->where('id', $event->id)
                                ->whereNull('push_notified_at')
                                ->update(['push_notified_at' => now(), 'updated_at' => now()]);
                            $sent++;
                        }
                    }
                }, 'id');
        }

        $this->info("Created or retried {$sent} subscription expiry notices for {$targetExpiry}.");
        return self::SUCCESS;
    }
}
