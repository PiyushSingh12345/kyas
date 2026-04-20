<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

class EnforceSessionTimeout
{
    /**
     * Enforce server-side inactivity timeout for authenticated sessions.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $timeoutMinutes = max((int) config('session.inactivity_timeout', 12), 1);
            $timeoutSeconds = $timeoutMinutes * 60;
            $lastActivity = $request->session()->get('last_activity_at');
            $now = time();

            if (is_numeric($lastActivity) && ($now - (int) $lastActivity) > $timeoutSeconds) {
                Auth::logout();

                $request->session()->invalidate();
                $request->session()->regenerateToken();

                if (! $request->expectsJson() && Route::has('login')) {
                    return redirect()->route('login')->with([
                        'status' => 'Your session expired due to inactivity. Please log in again.',
                        'status_type' => 'warning',
                    ]);
                }
            } else {
                $request->session()->put('last_activity_at', $now);
            }
        }

        return $next($request);
    }
}
