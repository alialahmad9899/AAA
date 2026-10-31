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

        // السماح بطلب الصورة الأولية أثناء إكمال التسجيل فقط؛ لا نفتح بقية API قبل التحقق.
        if (
            $user &&
            $request->is('api/member/basic-info/update') &&
            $request->hasFile('photo')
        ) {
            return $next($request);
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
