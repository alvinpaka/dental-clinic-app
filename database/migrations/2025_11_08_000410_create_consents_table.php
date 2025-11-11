<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('template_id')->nullable()->constrained('consent_templates')->nullOnDelete();
            $table->unsignedInteger('template_version')->nullable();
            $table->string('title');
            $table->longText('content_snapshot');
            $table->string('signed_by_name');
            $table->foreignId('signed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('signed_at');
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
            $table->index(['patient_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consents');
    }
};
