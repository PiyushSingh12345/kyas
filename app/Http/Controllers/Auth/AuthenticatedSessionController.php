<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // return redirect()->intended(route('dashboard', absolute: false));

        $user = $request->user();

        // Convert user_type_id to array if stored as CSV
        $userTypes = is_array($user->user_type_id)
            ? $user->user_type_id
            : explode(',', $user->user_type_id);

        // Redirect based on user type
        if (in_array(1, $userTypes)) {
             return redirect()->intended('/user-listing'); // KY_Admin
        } elseif (in_array(2, $userTypes)) {
            return redirect()->intended('/budget-phase'); // KY_User
        } elseif (in_array(3, $userTypes)) {
            return redirect()->intended('/budget-phase'); // Master Data User
        } elseif (in_array(4, $userTypes)) {
            return redirect()->intended('/budget-phase-report'); // PD Viewer
        // } elseif (in_array(5, $userTypes)) {
        //     return redirect()->intended('/csna-user'); // CSNA User
        } else {
            return redirect()->intended('/dashboard'); // Default  (need to ask with team)
        }

    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
