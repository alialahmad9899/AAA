<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckActiveUser
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::user()->deactivated == 0) {
            return $next($request);
        }
        flash(translate('This action is not allowed for deactivated accounts. Please reactivate your account to continue.'))->error();
        return back();
    }
}
