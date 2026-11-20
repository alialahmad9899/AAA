<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class IsApiMember
{
    private static ?bool $hasLastSeenAtColumn = null;

    /**
     * The mobile application does not require email verification or admin approval.
     * Keep the security checks that matter here: an authenticated account and
     * an explicit block status. Authentication is also enforced by auth:sanctum.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'result' => false,
                'status' => 'unauthenticated',
                'message' => translate('Unauthenticated.'),
            ], 401);
        }

        if ((int) $user->blocked === 1) {
            return response()->json([
                'result' => false,
                'status' => 'blocked',
                'message' => translate('User is banned.'),
            ], 403);
        }

        // Refresh the shared online marker for mobile API activity.
        $activityAt = now();
        Cache::put(
            'user-is-online-' . $user->id,
            true,
            $activityAt->copy()->addMinutes(3)
        );
        Cache::forever(
            'wesal:last-seen:' . $user->id,
            $activityAt->toIso8601String()
        );

        // Cache remains the fast presence source. Persist a durable fallback
        // once per minute when the additive users.last_seen_at column exists.
        try {
            if (self::$hasLastSeenAtColumn === null) {
                self::$hasLastSeenAtColumn = Schema::hasColumn('users', 'last_seen_at');
            }

            if (self::$hasLastSeenAtColumn) {
                $persistKey = 'wesal:last-seen-db-write:' . $user->id;
                if (Cache::add(
                    $persistKey,
                    true,
                    $activityAt->copy()->addMinute()
                )) {
                    try {
                        DB::table('users')
                            ->where('id', $user->id)
                            ->update([
                                'last_seen_at' => $activityAt->toDateTimeString(),
                            ]);
                    } catch (\\Throwable $exception) {
                        Cache::forget($persistKey);
                        Log::warning('Could not persist mobile last-seen timestamp.', [
                            'user_id' => (int) $user->id,
                            'error' => $exception->getMessage(),
                        ]);
                    }
                }
            }
        } catch (\\Throwable $exception) {
            // Presence persistence must never make an otherwise valid API
            // request fail, especially before the database column is deployed.
            Log::warning('Mobile last-seen persistence check failed.', [
                'user_id' => (int) $user->id,
                'error' => $exception->getMessage(),
            ]);
        }

        return $next($request);
    }
}
