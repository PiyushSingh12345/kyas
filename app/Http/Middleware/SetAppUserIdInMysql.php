<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SetAppUserIdInMysql
{
    public function handle($request, Closure $next)
    {
        if (Auth::check()) {
            $userId = Auth::id(); // logged-in user ID
            DB::statement("SET @app_user_id := ?", [$userId]);
        }

        return $next($request);
    }
}
