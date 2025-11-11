<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('cash_sessions', function (Blueprint $table) {
            $table->decimal('expected_cash_at_close', 12, 2)->nullable()->after('closing_amount');
            $table->decimal('variance', 12, 2)->nullable()->after('expected_cash_at_close');
        });
    }

    public function down(): void
    {
        Schema::table('cash_sessions', function (Blueprint $table) {
            $table->dropColumn(['expected_cash_at_close', 'variance']);
        });
    }
};
