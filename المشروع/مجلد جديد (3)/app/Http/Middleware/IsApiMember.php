<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class IsApiMember
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
        if ($request->is('api/*') && $user->blocked == 1) {

            if($user->approved == 0){
                return response()->json([
                    'result' => false,
                    'status' => 'non_verified',
                    'message' => translate('User is not verified')
                ]);
            }
            elseif($user->blocked == 1){
                return response()->json([
                    'result' => false,
                    'status' => 'blocked',
                    'message' => translate('user is banned')
                ]);
            }
        }
        return $next($request);
    }
}
