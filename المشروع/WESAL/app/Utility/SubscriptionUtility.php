<?php

namespace App\Utility;

use App\Models\Member;
use App\Models\ChatThread;
use App\Models\MessageQuotaUsage;
use App\Models\Package;
use App\Models\PackagePayment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The single source of truth for paid membership state and entitlements.
 * package_validity is inclusive: the member keeps access through that date.
 */
class SubscriptionUtility
{
    public static function constrainCurrentPaidMembers(Builder $users): Builder
    {
        $today = now()->toDateString();

        return $users->whereHas('member', function ($members) use ($today) {
                $members->whereNotNull('current_package_id')
                    ->whereNotNull('package_validity')
                    ->whereDate('package_validity', '>=', $today)
                    ->whereHas('package', function ($packages) {
                        $packages->whereNull('packages.deleted_at')
                            ->where('packages.active', 1)
                            ->where('packages.price', '>', 0)
                            ->where('packages.validity', '>', 0);
                    });
            });
    }

    /**
     * Constrain a user query to accounts without a currently valid paid package.
     * This keeps expired/disabled subscriptions out of Premium lists immediately,
     * even before the scheduled cleanup synchronizes the legacy membership flag.
     */
    public static function constrainNonPaidMembers(Builder $users): Builder
    {
        $today = now()->toDateString();

        return $users->whereDoesntHave('member', function ($members) use ($today) {
            $members->whereNotNull('current_package_id')
                ->whereNotNull('package_validity')
                ->whereDate('package_validity', '>=', $today)
                ->whereHas('package', function ($packages) {
                    $packages->whereNull('packages.deleted_at')
                        ->where('packages.active', 1)
                        ->where('packages.price', '>', 0)
                        ->where('packages.validity', '>', 0);
                });
        });
    }

    public static function currentActivePaidPackageForMember(?Member $member): ?Package
    {
        if (!$member || empty($member->current_package_id) || empty($member->package_validity)) {
            return null;
        }

        try {
            $expiryDate = Carbon::parse($member->package_validity)->toDateString();
        } catch (\Throwable $exception) {
            return null;
        }

        if ($expiryDate < now()->toDateString()) {
            return null;
        }

        return Package::query()
            ->whereKey($member->current_package_id)
            ->whereNull('packages.deleted_at')
            ->where('packages.active', 1)
            ->where('packages.price', '>', 0)
            ->where('packages.validity', '>', 0)
            ->first();
    }

    public static function currentActivePaidPackage($user): ?Package
    {
        if (!$user) {
            return null;
        }

        if (!$user instanceof User) {
            $user = User::with('member')->find($user);
        }

        if (!$user) {
            return null;
        }

        // Do not trust the legacy membership flag as the source of truth.
        // A valid paid current package remains active even if that flag drifted.
        return self::currentActivePaidPackageForMember($user->member);
    }

    public static function hasActivePaidPackage($user): bool
    {
        return self::currentActivePaidPackage($user) !== null;
    }

    /**
     * Calculate the inclusive last valid date for a package beginning on startDate.
     * A seven-day package starting on 2026-11-04 is valid through 2026-11-10.
     */
    public static function calculateExpiryDate(string $startDate, int $validityDays): string
    {
        if ($validityDays < 1) {
            throw new \InvalidArgumentException('Package validity must be at least one day.');
        }

        $start = new \DateTimeImmutable($startDate);
        return $start->modify('+' . ($validityDays - 1) . ' days')->format('Y-m-d');
    }

    /**
     * Renewal starts on the day after the current paid subscription expires.
     * A new/expired subscription starts today.
     */
    public static function calculateNextExpiryDate(Member $member, Package $package, bool $continueExistingPaidPackage = false): string
    {
        $days = (int) $package->validity;
        if ($days < 1 || (float) $package->price <= 0 || (int) $package->active !== 1) {
            throw new \InvalidArgumentException('Only active paid packages with a positive validity can be activated.');
        }

        $canExtend = $continueExistingPaidPackage && self::currentActivePaidPackageForMember($member) !== null;
        $startDate = $canExtend
            ? Carbon::parse($member->package_validity)->addDay()->toDateString()
            : now()->toDateString();

        return self::calculateExpiryDate($startDate, $days);
    }

    /**
     * Execute a quota-consuming database action atomically.
     * Free female accounts retain the established unlimited free-access policy;
     * every other account must hold a currently valid paid package and quota.
     * The callback must create the requested record and return a truthy result.
     */
    public static function withQuota(int $userId, string $quotaColumn, callable $action)
    {
        $allowedColumns = [
            'remaining_interest',
            'remaining_photo_gallery',
            'remaining_contact_view',
            'remaining_profile_viewer_view',
            'remaining_profile_image_view',
            'remaining_gallery_image_view',
        ];
        if (!in_array($quotaColumn, $allowedColumns, true)) {
            throw new \InvalidArgumentException('Unsupported subscription quota column.');
        }

        return DB::transaction(function () use ($userId, $quotaColumn, $action) {
            $user = User::whereKey($userId)->lockForUpdate()->first();
            $member = Member::where('user_id', $userId)->lockForUpdate()->first();
            if (!$user || !$member) {
                return null;
            }

            $isFemale = MemberUtility::is_female($user);
            $hasActivePaidPackage = self::currentActivePaidPackageForMember($member) !== null;
            $quota = (int) ($member->{$quotaColumn} ?? 0);
            $freeProfileViewerAllowance = !$isFemale
                && !$hasActivePaidPackage
                && $quotaColumn === 'remaining_profile_viewer_view';

            if (!$isFemale) {
                if ($freeProfileViewerAllowance) {
                    if (ProfileViewer::where('user_id', $user->id)->count() >= 3) {
                        return null;
                    }
                } elseif (!$hasActivePaidPackage || ($quota !== -1 && $quota <= 0)) {
                    return null;
                }
            }

            $result = $action($user, $member);
            if ($result === null || $result === false) {
                return $result;
            }

            if (!$isFemale && $hasActivePaidPackage && $quota > 0) {
                $member->{$quotaColumn} = $quota - 1;
                $member->save();
            }
            return $result;
        });
    }

    /**
     * Find or create a conversation thread while enforcing the sender's current
     * paid-plan state and the package's unique-recipient quota atomically.
     * Female accounts keep the application's existing free-chat policy.
     */
    public static function getOrCreateChatThread(int $senderId, int $receiverId): array
    {
        if ($senderId < 1 || $receiverId < 1 || $senderId === $receiverId) {
            return ['status' => 'invalid_target', 'thread' => null];
        }

        return DB::transaction(function () use ($senderId, $receiverId) {
            $user = User::whereKey($senderId)->lockForUpdate()->first();
            if (!$user || !User::whereKey($receiverId)->where('user_type', 'member')->exists()) {
                return ['status' => 'invalid_target', 'thread' => null];
            }

            $thread = ChatThread::where(function ($query) use ($senderId, $receiverId) {
                $query->where('sender_user_id', $senderId)->where('receiver_user_id', $receiverId);
            })->orWhere(function ($query) use ($senderId, $receiverId) {
                $query->where('sender_user_id', $receiverId)->where('receiver_user_id', $senderId);
            })->first();

            if ($thread) {
                return ['status' => 'existing', 'thread' => $thread];
            }

            $member = Member::where('user_id', $senderId)->lockForUpdate()->first();
            $isFemale = MemberUtility::is_female($user);
            $activePackage = self::currentActivePaidPackageForMember($member);
            $alreadyContacted = MessageQuotaUsage::where('user_id', $senderId)
                ->where('recipient_id', $receiverId)
                ->exists();

            // Previously unlocked recipients remain available after package expiry.
            if (!$isFemale && !$activePackage && !$alreadyContacted) {
                return ['status' => 'subscription_expired', 'thread' => null];
            }

            if (!$isFemale && !$alreadyContacted) {
                $recipientLimit = (int) ($activePackage->message_profile_quota ?? 0);
                if ($recipientLimit !== -1) {
                    $contactedCount = MessageQuotaUsage::where('user_id', $senderId)->count();
                    if ($contactedCount >= max(0, $recipientLimit)) {
                        return ['status' => 'message_profile_quota', 'thread' => null];
                    }
                }
            }

            $thread = new ChatThread();
            $thread->thread_code = date('YmdHis') . random_int(10, 99);
            $thread->sender_user_id = $senderId;
            $thread->receiver_user_id = $receiverId;
            $thread->active = 1;
            $thread->interview = 0;
            $thread->save();

            MessageQuotaUsage::firstOrCreate([
                'user_id' => $senderId,
                'recipient_id' => $receiverId,
            ]);

            return ['status' => 'created', 'thread' => $thread];
        });
    }

    /** Add package quota while preserving the -1 unlimited convention. */
    public static function addQuota($current, $granted): int
    {
        $current = (int) $current;
        $granted = (int) $granted;

        if ($current === -1 || $granted === -1) {
            return -1;
        }

        return max(0, $current) + max(0, $granted);
    }

    /** Apply the quota values stored on this exact package. */
    public static function applyPackageEntitlements(Member $member, Package $package, bool $continueExistingPaidPackage): void
    {
        if ((float) $package->price <= 0 || (int) $package->validity < 1 || (int) $package->active !== 1) {
            throw new \InvalidArgumentException('Cannot grant premium entitlements from an inactive, free or invalid package.');
        }

        if (!$continueExistingPaidPackage) {
            self::clearEntitlements($member);
        }

        $member->remaining_interest = self::addQuota($member->remaining_interest, $package->express_interest);
        $member->remaining_photo_gallery = self::addQuota($member->remaining_photo_gallery, $package->photo_gallery);
        $member->remaining_contact_view = self::addQuota($member->remaining_contact_view, $package->contact);
        $member->remaining_profile_viewer_view = self::addQuota($member->remaining_profile_viewer_view, $package->profile_viewers_view);
        $member->remaining_profile_image_view = self::addQuota($member->remaining_profile_image_view, $package->profile_image_view);
        $member->remaining_gallery_image_view = self::addQuota($member->remaining_gallery_image_view, $package->gallery_image_view);
        $member->auto_profile_match = (int) ($package->auto_profile_match ?? 0);
    }

    public static function clearEntitlements(Member $member): void
    {
        $member->remaining_interest = 0;
        $member->remaining_photo_gallery = 0;
        $member->remaining_contact_view = 0;
        $member->remaining_profile_viewer_view = 0;
        $member->remaining_profile_image_view = 0;
        $member->remaining_gallery_image_view = 0;
        $member->auto_profile_match = 0;
    }

    public static function recordLifecycleEvent(
        int $userId,
        int $memberId,
        ?int $packageId,
        string $eventType,
        string $expiryDate,
        ?int $actorId = null,
        array $details = []
    ): void {
        if (!Schema::hasTable('subscription_events')) {
            return;
        }

        DB::table('subscription_events')->insertOrIgnore([
            'user_id' => $userId,
            'member_id' => $memberId,
            'package_id' => $packageId,
            'event_type' => $eventType,
            'subscription_expires_at' => Carbon::parse($expiryDate)->toDateString(),
            'actor_id' => $actorId,
            'details' => json_encode($details, JSON_UNESCAPED_UNICODE),
            'notified_at' => null,
            'push_notified_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Clear expired/disabled/deleted paid status under row locks.
     * This is safe to call from a request as well as from the scheduled job.
     */
    public static function deactivateIfInvalid($user): bool
    {
        if (!$user) {
            return false;
        }

        if (!$user instanceof User) {
            $user = User::with('member')->find($user);
        }
        if (!$user) {
            return false;
        }

        return DB::transaction(function () use ($user) {
            $lockedUser = User::whereKey($user->id)->lockForUpdate()->first();
            if (!$lockedUser) {
                return false;
            }

            $member = Member::where('user_id', $lockedUser->id)->lockForUpdate()->first();
            if (!$member) {
                if ((int) $lockedUser->membership !== 2) {
                    return false;
                }
                $lockedUser->membership = 1;
                $lockedUser->save();
                return true;
            }

            $activePackage = self::currentActivePaidPackageForMember($member);
            if ($activePackage) {
                // The current paid package and inclusive expiry date are authoritative.
                // Repair a stale legacy membership flag instead of revoking a valid plan.
                if ((int) $lockedUser->membership !== 2) {
                    $lockedUser->membership = 2;
                    $lockedUser->save();
                }
                return false;
            }

            // Free/default package records may legitimately remain linked to free accounts.
            // Only revoke a non-Premium account's stale package state when the linked package
            // was actually paid; otherwise preserve the free-plan entitlements and policy.
            $previousPackage = $member->current_package_id
                ? Package::withTrashed()->find($member->current_package_id)
                : null;
            $wasPaidPackage = $previousPackage && (float) $previousPackage->price > 0;

            // A package may have been permanently deleted instead of soft-deleted.
            // In that case, retain the payment history as evidence that this was a paid plan,
            // so stale Premium quotas are still revoked even if the legacy membership flag drifted.
            if (!$previousPackage && !empty($member->current_package_id)) {
                $wasPaidPackage = PackagePayment::query()
                    ->where('user_id', $lockedUser->id)
                    ->where('package_id', $member->current_package_id)
                    ->where('payment_status', 'Paid')
                    ->exists();
            }

            $hasStalePremiumState = (int) $lockedUser->membership === 2 || $wasPaidPackage;

            if (!$hasStalePremiumState) {
                return false;
            }

            $oldPackageId = $member->current_package_id;
            $oldExpiry = $member->package_validity;
            $expiryDate = $oldExpiry ? Carbon::parse($oldExpiry)->toDateString() : null;
            $eventType = $expiryDate && $expiryDate < now()->toDateString()
                ? 'subscription_expired'
                : 'subscription_invalidated';

            $member->current_package_id = null;
            $member->package_validity = null;
            self::clearEntitlements($member);
            $member->save();

            $lockedUser->membership = 1;
            $lockedUser->save();

            if ($oldExpiry) {
                self::recordLifecycleEvent(
                    (int) $lockedUser->id,
                    (int) $member->id,
                    $oldPackageId ? (int) $oldPackageId : null,
                    $eventType,
                    Carbon::parse($oldExpiry)->toDateString(),
                    null,
                    ['reason' => $eventType]
                );
            }

            return true;
        });
    }

    public static function cancelSubscription(int $memberId, int $actorId, ?string $reason = null): array
    {
        return DB::transaction(function () use ($memberId, $actorId, $reason) {
            $memberReference = Member::select('user_id')->whereKey($memberId)->firstOrFail();
            $user = User::whereKey($memberReference->user_id)->lockForUpdate()->firstOrFail();
            $member = Member::whereKey($memberId)->lockForUpdate()->firstOrFail();
            if ((int) $member->user_id !== (int) $user->id) {
                throw new \RuntimeException('MEMBER_OWNER_CHANGED');
            }
            $packageId = $member->current_package_id;
            $expiry = $member->package_validity;

            $hasSubscription = (int) $user->membership === 2
                || self::currentActivePaidPackageForMember($member) !== null;

            if (!$hasSubscription) {
                return ['cancelled' => false, 'already_inactive' => true];
            }

            if (!$expiry) {
                $expiry = now()->toDateString();
            }

            self::recordLifecycleEvent(
                (int) $user->id,
                (int) $member->id,
                $packageId ? (int) $packageId : null,
                'admin_cancelled',
                Carbon::parse($expiry)->toDateString(),
                $actorId,
                ['reason' => trim((string) $reason)]
            );

            $member->current_package_id = null;
            $member->package_validity = null;
            self::clearEntitlements($member);
            $member->save();

            $user->membership = 1;
            $user->save();

            return [
                'cancelled' => true,
                'user_id' => $user->id,
                'member_id' => $member->id,
                'package_id' => $packageId,
                'expiry_date' => $expiry,
            ];
        });
    }

    /** Deactivate every account still marked Premium but no longer entitled. */
    public static function deactivateInvalidSubscriptions(): int
    {
        $deactivated = 0;

        User::query()
            ->where('user_type', 'member')
            ->where(function ($query) {
                $query->where('membership', 2)
                    ->orWhereHas('member', function ($members) {
                        $members->whereHas('package', function ($packages) {
                            $packages->where('price', '>', 0);
                        })->orWhere(function ($stale) {
                            $stale->whereNotNull('current_package_id')->whereDoesntHave('package');
                        });
                    });
            })
            ->with('member')
            ->orderBy('id')
            ->chunkById(100, function ($users) use (&$deactivated) {
                foreach ($users as $user) {
                    if (self::deactivateIfInvalid($user)) {
                        $deactivated++;
                    }
                }
            });

        return $deactivated;
    }
}
