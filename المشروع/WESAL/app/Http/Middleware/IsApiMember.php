<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

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

        return $next($request);
    }
}
