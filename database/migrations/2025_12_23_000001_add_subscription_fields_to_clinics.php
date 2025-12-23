<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clinics', function (Blueprint $table) {
            $table->string('subscription_plan')->default('basic')->after('subscription_status');
            $table->timestamp('subscription_ends_at')->nullable()->after('subscription_plan');
            $table->timestamp('trial_ends_at')->nullable()->after('subscription_ends_at');
            $table->string('stripe_customer_id')->nullable()->after('trial_ends_at');
            $table->string('stripe_subscription_id')->nullable()->after('stripe_customer_id');
            $table->string('stripe_price_id')->nullable()->after('stripe_subscription_id');
            $table->json('subscription_limits')->nullable()->after('stripe_price_id');
        });
    }

    public function down(): void
    {
        Schema::table('clinics', function (Blueprint $table) {
            $table->dropColumn([
                'subscription_plan',
                'subscription_ends_at',
                'trial_ends_at',
                'stripe_customer_id',
                'stripe_subscription_id',
                'stripe_price_id',
                'subscription_limits'
            ]);
        });
    }
};
