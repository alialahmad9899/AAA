<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class IsApiMember
{
    private const PROFILE_SETUP_READ_PATHS = [
        'api/member/education',
        'api/member/career',
        'api/member/languages',
        'api/member/partner-languages',
        'api/member/profile-dropdown',
        'api/member/countries',
        'api/member/states/*',
    ];

    private const PROFILE_SETUP_WRITE_PATHS = [
        'api/member/basic-info/update',
        'api/member/physical-attributes/update',
        'api/member/residency-info/update',
        'api/member/spiritual-background/update',
        'api/member/partner-expectation/update',
        'api/member/life-style/update',
        'api/member/attitude-behavior/update',
        'api/member/astronomic/update',
        'api/member/education',
        'api/member/education/*',
        'api/member/career',
        'api/member/career/*',
    ];

    private function isProfileSetupRequest(Request $request): bool
    {
        $method = strtoupper($request->method());
        if ($method === 'GET') {
            $paths = self::PROFILE_SETUP_READ_PATHS;
        } elseif (in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
            $paths = self::PROFILE_SETUP_WRITE_PATHS;
        } else {
            $paths = [];
        }

        foreach ($paths as $path) {
            if ($request->is($path)) {
                return true;
            }
        }

        return false;
    }

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

        // Pending admin approval may finish profile setup only.
        if ((int) $user->approved === 0 && !$this->isProfileSetupRequest($request)) {
            return response()->json([
                'result' => false,
                'status' => 'non_verified',
                'message' => translate('User is not verified.'),
            ], 403);
        }

        return $next($request);
    }
}
