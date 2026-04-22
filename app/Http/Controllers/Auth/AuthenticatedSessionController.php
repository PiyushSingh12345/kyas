<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
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
            'statusType' => session('status_type'),
            'recaptcha' => [
                'enabled' => (bool) config('services.recaptcha.enabled')
                    && ! empty(config('services.recaptcha.site_key'))
                    && ! empty(config('services.recaptcha.secret_key')),
                'version' => config('services.recaptcha.version', 'v2'),
                'siteKey' => config('services.recaptcha.site_key'),
            ],
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

        if (! Schema::hasColumn('users', 'active_session_id')) {
            return $this->redirectByUserType($user->user_type_id);
        }

        $currentSessionId = (string) $request->session()->getId();
        $previousSessionId = (string) ($user->active_session_id ?? '');

        if ($previousSessionId !== '' && $previousSessionId !== $currentSessionId && config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))
                ->where('id', $previousSessionId)
                ->delete();
        }

        if ($previousSessionId !== $currentSessionId) {
            $user->forceFill([
                'active_session_id' => $currentSessionId,
            ])->save();
        }

        return $this->redirectByUserType($user->user_type_id);

    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();
        $currentSessionId = (string) $request->session()->getId();

        if ($user && Schema::hasColumn('users', 'active_session_id') && (string) ($user->active_session_id ?? '') === $currentSessionId) {
            $user->forceFill([
                'active_session_id' => null,
            ])->save();
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }

    private function redirectByUserType(array|string|null $userTypeId): RedirectResponse
    {
        // Convert user_type_id to array if stored as CSV
        $userTypes = is_array($userTypeId)
            ? $userTypeId
            : explode(',', (string) $userTypeId);

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
}
