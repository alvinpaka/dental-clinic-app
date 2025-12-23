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
        Schema::table('audit_logs', function (Blueprint $table) {
            // Add clinic_id if it doesn't exist
            if (!Schema::hasColumn('audit_logs', 'clinic_id')) {
                $table->foreignId('clinic_id')->nullable()->constrained()->onDelete('set null');
            }
            
            // Rename metadata to old_values if needed
            if (Schema::hasColumn('audit_logs', 'metadata') && !Schema::hasColumn('audit_logs', 'old_values')) {
                $table->renameColumn('metadata', 'old_values');
            }
            
            // Add new_values if it doesn't exist
            if (!Schema::hasColumn('audit_logs', 'new_values')) {
                $table->json('new_values')->nullable();
            }
            
            // Add indexes only if they don't exist
            if (!Schema::hasIndex('audit_logs', 'audit_logs_subject_type_subject_id_index')) {
                $table->index(['subject_type', 'subject_id']);
            }
            if (!Schema::hasIndex('audit_logs', 'audit_logs_clinic_id_created_at_index')) {
                $table->index(['clinic_id', 'created_at']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropForeign(['clinic_id']);
            $table->dropColumn(['clinic_id', 'new_values']);
        });
    }
};
