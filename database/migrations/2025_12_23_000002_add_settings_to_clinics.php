<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clinics', function (Blueprint $table) {
            $table->json('settings')->nullable()->after('subscription_limits');
            $table->string('primary_color')->default('#3B82F6')->after('settings');
            $table->string('secondary_color')->default('#64748B')->after('primary_color');
            $table->string('timezone')->default('UTC')->after('secondary_color');
            $table->string('currency')->default('USD')->after('timezone');
            $table->json('business_hours')->nullable()->after('currency');
        });
    }

    public function down(): void
    {
        Schema::table('clinics', function (Blueprint $table) {
            $table->dropColumn([
                'settings',
                'primary_color',
                'secondary_color',
                'timezone',
                'currency',
                'business_hours'
            ]);
        });
    }
};
