<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class EnsureClinicAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip middleware for guest users or super admins
        if (!Auth::check() || Auth::user()->hasRole('super-admin')) {
            return $next($request);
        }

        $user = Auth::user();

        // Ensure user has a clinic assigned
        if (!$user->clinic_id) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'User is not assigned to any clinic',
                    'message' => 'Please contact your administrator to be assigned to a clinic.'
                ], 403);
            }

            abort(403, 'You are not assigned to any clinic. Please contact your administrator.');
        }

        // Check if clinic is active
        if (!$user->clinic || !$user->clinic->is_active) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Clinic is not active',
                    'message' => 'Your clinic is currently inactive. Please contact your administrator.'
                ], 403);
            }

            abort(403, 'Your clinic is currently inactive. Please contact your administrator.');
        }

        // Check subscription status
        if ($user->clinic->subscription_status === 'expired' || $user->clinic->subscription_status === 'cancelled') {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Clinic subscription is not active',
                    'message' => 'Your clinic subscription has expired or been cancelled. Please renew your subscription to continue using the service.'
                ], 403);
            }

            abort(403, 'Your clinic subscription is not active. Please renew your subscription to continue using the service.');
        }

        return $next($request);
    }
}
