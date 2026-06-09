<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $userTypeIds = $user ? array_map('intval', explode(',', $user->user_type_id)) : [];
        
        // Get user type names for better frontend handling
        $userTypeNames = [];
        if ($user && $user->user_type_id) {
            $userTypes = \App\Models\MdUserType::whereIn('md_user_type_id', $userTypeIds)
                ->where('is_active', 1)
                ->pluck('user_type_name', 'md_user_type_id')
                ->toArray();
            $userTypeNames = $userTypes;
        }

        return [
            ...parent::share($request),
            'csrf_token' => csrf_token(),
            'auth' => [
                'user' => $user,
                'user_type_ids' => $userTypeIds,
                'user_type_names' => $userTypeNames,
                'is_admin' => in_array(1, $userTypeIds),
                'is_ky_user' => in_array(2, $userTypeIds),
                'is_master_data_user' => in_array(3, $userTypeIds),
                'is_pd_viewer' => in_array(4, $userTypeIds),
                'is_csna_user' => in_array(5, $userTypeIds),
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'session' => [
                'inactivity_timeout_minutes' => (int) config('session.inactivity_timeout', 12),
            ],
        ];
    }
}
