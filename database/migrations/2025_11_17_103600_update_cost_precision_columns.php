<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // For SQLite compatibility, we'll use the schema builder methods
        
        if (Schema::hasColumn('treatments', 'cost')) {
            Schema::table('treatments', function ($table) {
                $table->decimal('cost', 12, 2)->change();
            });
        }

        if (Schema::hasColumn('treatments', 'prescription_amount')) {
            Schema::table('treatments', function ($table) {
                $table->decimal('prescription_amount', 12, 2)->nullable()->change();
            });
        }

        if (Schema::hasTable('treatment_procedures') && Schema::hasColumn('treatment_procedures', 'cost')) {
            Schema::table('treatment_procedures', function ($table) {
                $table->decimal('cost', 12, 2)->default(0)->change();
            });
        }

        if (Schema::hasTable('invoices') && Schema::hasColumn('invoices', 'amount')) {
            Schema::table('invoices', function ($table) {
                $table->decimal('amount', 12, 2)->change();
            });
        }

        if (Schema::hasTable('prescriptions') && Schema::hasColumn('prescriptions', 'prescription_amount')) {
            Schema::table('prescriptions', function ($table) {
                $table->decimal('prescription_amount', 12, 2)->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        // Reverting the changes in the down method using schema builder
        if (Schema::hasColumn('treatments', 'cost')) {
            Schema::table('treatments', function ($table) {
                $table->decimal('cost', 8, 2)->change();
            });
        }

        if (Schema::hasColumn('treatments', 'prescription_amount')) {
            Schema::table('treatments', function ($table) {
                $table->decimal('prescription_amount', 8, 2)->nullable()->change();
            });
        }

        if (Schema::hasTable('treatment_procedures') && Schema::hasColumn('treatment_procedures', 'cost')) {
            Schema::table('treatment_procedures', function ($table) {
                $table->decimal('cost', 8, 2)->default(0)->change();
            });
        }

        if (Schema::hasTable('invoices') && Schema::hasColumn('invoices', 'amount')) {
            Schema::table('invoices', function ($table) {
                $table->decimal('amount', 8, 2)->change();
            });
        }

        if (Schema::hasTable('prescriptions') && Schema::hasColumn('prescriptions', 'prescription_amount')) {
            Schema::table('prescriptions', function ($table) {
                $table->decimal('prescription_amount', 8, 2)->nullable()->change();
            });
        }
    }
};
