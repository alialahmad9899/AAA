<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;

class EmailVerifiedApi
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        // السماح بكتابات إكمال الملف الشخصي للمستخدم الموثق حسابياً،
        // حتى يستطيع إتمام التسجيل قبل تأكيد البريد. لا يشمل ذلك أي طلبات قراءة أو محادثات.
        $profileSetupWritePaths = [
            'api/member/basic-info/update',
            'api/member/physical-attributes/update',
            'api/member/residency-info/update',
            'api/member/spiritual-background/update',
            'api/member/partner-expectation/update',
            'api/member/education',
            'api/member/education/*',
            'api/member/career',
            'api/member/career/*',
        ];

        if ($user && in_array(strtoupper($request->method()), ['POST', 'PUT', 'PATCH'], true)) {
            foreach ($profileSetupWritePaths as $allowedPath) {
                if ($request->is($allowedPath)) {
                    return $next($request);
                }
            }
        }

        if (
            !$request->user() ||
            ($request->user() instanceof MustVerifyEmail &&
                !$request->user()->hasVerifiedEmail())
        ) {
            return response()->json([
                'result' => false,
                'status' => 'un_verified',
                'message' => translate('Email is not verified!')
            ], 403);
        }
        return $next($request);
    }
}
