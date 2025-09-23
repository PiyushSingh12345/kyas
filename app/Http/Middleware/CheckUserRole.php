<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUserRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();
        
        if (!$user) {
            return redirect()->route('login');
        }

        // Get user type IDs from the user
        $userTypeIds = $user->user_type_id ? array_map('intval', explode(',', $user->user_type_id)) : [];
        
        // Check if user has any of the required roles
        $hasAccess = false;
        foreach ($roles as $role) {
            if (in_array((int)$role, $userTypeIds)) {
                $hasAccess = true;
                break;
            }
        }

        if (!$hasAccess) {
            // Redirect to unauthorized page or dashboard
            return redirect()->route('dashboard')->with('error', 'You do not have permission to access this page.');
        }

        return $next($request);
    }
}
