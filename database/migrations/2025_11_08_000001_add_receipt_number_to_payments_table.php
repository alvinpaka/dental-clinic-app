<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->unsignedBigInteger('receipt_number')->nullable()->unique()->after('id');
        });

        // Backfill existing payments with sequential numbers
        $next = 1;
        DB::table('payments')
            ->orderBy('id')
            ->select('id')
            ->chunkById(100, function ($rows) use (&$next) {
                foreach ($rows as $row) {
                    DB::table('payments')->where('id', $row->id)->update(['receipt_number' => $next++]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropUnique(['receipt_number']);
            $table->dropColumn('receipt_number');
        });
    }
};
