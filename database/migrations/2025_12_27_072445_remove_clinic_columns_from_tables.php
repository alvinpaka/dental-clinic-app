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
        // Remove clinic_id from users table
        if (Schema::hasColumn('users', 'clinic_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['clinic_id']);
                $table->dropColumn('clinic_id');
            });
        }

        // Remove clinic_id from patients table
        if (Schema::hasColumn('patients', 'clinic_id')) {
            Schema::table('patients', function (Blueprint $table) {
                $table->dropForeign(['clinic_id']);
                $table->dropColumn('clinic_id');
            });
        }

        // Remove clinic_id from appointments table
        if (Schema::hasColumn('appointments', 'clinic_id')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->dropForeign(['clinic_id']);
                $table->dropColumn('clinic_id');
            });
        }

        // Remove clinic_id from treatments table
        if (Schema::hasColumn('treatments', 'clinic_id')) {
            Schema::table('treatments', function (Blueprint $table) {
                $table->dropForeign(['clinic_id']);
                $table->dropColumn('clinic_id');
            });
        }

        // Remove clinic_id from invoices table
        if (Schema::hasColumn('invoices', 'clinic_id')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->dropForeign(['clinic_id']);
                $table->dropColumn('clinic_id');
            });
        }

        // Drop clinics table if it exists
        Schema::dropIfExists('clinics');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate clinics table
        if (!Schema::hasTable('clinics')) {
            Schema::create('clinics', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('phone')->nullable();
                $table->text('address')->nullable();
                $table->string('logo_path')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // Add clinic_id to users table
        if (!Schema::hasColumn('users', 'clinic_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('clinic_id')->nullable()->constrained('clinics')->onDelete('set null');
            });
        }

        // Add clinic_id to patients table
        if (!Schema::hasColumn('patients', 'clinic_id')) {
            Schema::table('patients', function (Blueprint $table) {
                $table->foreignId('clinic_id')->nullable()->constrained('clinics')->onDelete('cascade');
            });
        }

        // Add clinic_id to appointments table
        if (!Schema::hasColumn('appointments', 'clinic_id')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->foreignId('clinic_id')->nullable()->constrained('clinics')->onDelete('cascade');
            });
        }

        // Add clinic_id to treatments table
        if (!Schema::hasColumn('treatments', 'clinic_id')) {
            Schema::table('treatments', function (Blueprint $table) {
                $table->foreignId('clinic_id')->nullable()->constrained('clinics')->onDelete('cascade');
            });
        }

        // Add clinic_id to invoices table
        if (!Schema::hasColumn('invoices', 'clinic_id')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->foreignId('clinic_id')->nullable()->constrained('clinics')->onDelete('cascade');
            });
        }
    }
};
