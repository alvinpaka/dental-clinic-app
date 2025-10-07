<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Inertia::version(function () {
            if (file_exists(public_path('build/manifest.json'))) {
                return md5_file(public_path('build/manifest.json'));
            }
            return null;
        });
    }

    public function boot(): void
    {
        //
    }
}