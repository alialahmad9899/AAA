<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class IsApiMember
{
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

        return $next($request);
    }
}
