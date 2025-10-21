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
        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('treatment_id')->constrained()->onDelete('cascade');
            $table->foreignId('medicine_id')->nullable()->constrained('dental_medicines', 'medicine_id');
            $table->string('medication')->nullable();
            $table->string('dosage')->nullable();
            $table->string('frequency')->nullable();
            $table->string('duration')->nullable();
            $table->decimal('prescription_amount', 8, 2)->nullable();
            $table->date('prescription_issue_date')->nullable();
            $table->date('prescription_expiry_date')->nullable();
            $table->text('prescription_instructions')->nullable();
            $table->integer('max_refills')->nullable();
            $table->enum('prescription_status', ['active', 'completed', 'expired', 'cancelled'])->default('active');
            $table->integer('refill_count')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prescriptions');
    }
};
