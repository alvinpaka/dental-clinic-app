<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

// Define helper functions before they are used
if (!function_exists('extractRequiredRole')) {
    function extractRequiredRole(\Illuminate\Auth\Access\AuthorizationException $e): string
    {
        // Try to extract role information from the exception message or requirements
        $message = $e->getMessage();
        if (str_contains(strtolower($message), 'admin')) {
            return 'Admin';
        } elseif (str_contains(strtolower($message), 'receptionist')) {
            return 'Receptionist';
        } elseif (str_contains(strtolower($message), 'dentist')) {
            return 'Dentist';
        } elseif (str_contains(strtolower($message), 'assistant')) {
            return 'Assistant';
        }

        return '';
    }
}

if (!function_exists('extractResource')) {
    function extractResource(\Illuminate\Auth\Access\AuthorizationException $e): string
    {
        // Try to extract resource information from the exception
        $message = $e->getMessage();
        if (str_contains(strtolower($message), 'patient')) {
            return 'Patient Records';
        } elseif (str_contains(strtolower($message), 'appointment')) {
            return 'Appointments';
        } elseif (str_contains(strtolower($message), 'treatment')) {
            return 'Treatments';
        } elseif (str_contains(strtolower($message), 'invoice')) {
            return 'Invoices';
        } elseif (str_contains(strtolower($message), 'staff')) {
            return 'Staff Management';
        } elseif (str_contains(strtolower($message), 'inventory')) {
            return 'Inventory';
        } elseif (str_contains(strtolower($message), 'expense')) {
            return 'Expenses';
        } elseif (str_contains(strtolower($message), 'report')) {
            return 'Reports';
        }

        return '';
    }
}

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
        ]);
        
        $middleware->validateCsrfTokens(except: [
            '/api/chat/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Illuminate\Auth\Access\AuthorizationException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*') || $request->header('X-Inertia')) {
                return response()->json([
                    'title' => 'Access Denied',
                    'message' => 'You do not have permission to access this page.',
                    'required_role' => extractRequiredRole($e),
                    'resource' => extractResource($e),
                ], 403);
            }

            // For non-AJAX requests, use default behavior
            return null;
        });
    })->create();
