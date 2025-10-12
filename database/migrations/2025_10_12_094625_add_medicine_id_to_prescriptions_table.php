<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->foreignId('medicine_id')->nullable()->constrained('dental_medicines', 'medicine_id')->onDelete('set null');
            $table->text('medication')->nullable()->change();
            $table->string('frequency')->nullable();
            $table->string('duration')->nullable();
            $table->date('expiry_date')->nullable();
            $table->integer('max_refills')->nullable();
            $table->enum('status', ['active', 'completed', 'expired', 'cancelled'])->default('active');
            $table->integer('refill_count')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->dropForeign(['medicine_id']);
            $table->dropColumn(['medicine_id', 'frequency', 'duration', 'expiry_date', 'max_refills', 'status', 'refill_count']);
            $table->text('medication')->nullable(false)->change();
        });
    }
};
