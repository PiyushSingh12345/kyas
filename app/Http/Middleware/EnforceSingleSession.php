<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class EnforceSingleSession
{
    /**
     * Ensure only the latest login session remains active.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return $next($request);
        }

        if (! Schema::hasColumn('users', 'active_session_id')) {
            return $next($request);
        }

        $user = $request->user();
        $activeSessionId = (string) ($user?->active_session_id ?? '');
        $currentSessionId = (string) $request->session()->getId();

        if ($activeSessionId !== '' && ! hash_equals($activeSessionId, $currentSessionId)) {
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if (! $request->expectsJson() && Route::has('login')) {
                return redirect()->route('login')->with([
                    'status' => 'You have been logged out because your account was used to sign in from another device or browser.',
                    'status_type' => 'warning',
                ]);
            }
        }

        return $next($request);
    }
}
