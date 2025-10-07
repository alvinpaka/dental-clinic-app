<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropForeign(['dentist_id']);
            $table->foreignId('dentist_id')->nullable()->change();
            $table->foreign('dentist_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropForeign(['dentist_id']);
            $table->foreignId('dentist_id')->nullable(false)->change();
            $table->foreign('dentist_id')->references('id')->on('users')->onDelete('cascade');
        });
    }
};
