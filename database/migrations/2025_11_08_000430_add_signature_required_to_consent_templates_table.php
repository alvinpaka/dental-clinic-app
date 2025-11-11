<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('consent_templates', function (Blueprint $table) {
            $table->boolean('signature_required')->default(true)->after('active');
        });
    }

    public function down(): void
    {
        Schema::table('consent_templates', function (Blueprint $table) {
            $table->dropColumn('signature_required');
        });
    }
};
