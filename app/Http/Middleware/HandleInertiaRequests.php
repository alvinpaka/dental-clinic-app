<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use App\Models\Clinic;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'auth' => [
                'user' => function () use ($request) {
                    $user = $request->user();
                    if (!$user) {
                        return null;
                    }
                    
                    // Load relationships to ensure they're available
                    $user->load(['roles', 'permissions']);
                    
                    return [
                        'id' => (int) $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'roles' => $user->roles,
                        'permissions' => $user->getAllPermissions()->pluck('name'),
                        'clinic_id' => $user->clinic_id ? (int) $user->clinic_id : null,
                    ];
                },
            ],
            'currentClinic' => function () use ($request) {
                $user = $request->user();
                if (!$user || !$user->clinic_id) {
                    return null;
                }
                
                $clinic = Clinic::find($user->clinic_id);
                if (!$clinic) {
                    return null;
                }
                
                return [
                    'id' => (int) $clinic->id,
                    'name' => $clinic->name,
                    'email' => $clinic->email,
                ];
            },
            'flash' => [
                'message' => fn () => $request->session()->get('message'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'errors' => fn () => $request->session()->get('errors')
                ? $request->session()->get('errors')->getBag('default')->getMessages()
                : (object) [],
            'unauthorized' => fn () => $request->session()->get('unauthorized'),
        ]);
    }
}
