<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('treatment_procedures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('treatment_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->decimal('cost', 12, 2)->default(0);
            $table->timestamps();
        });

        if (Schema::hasColumn('treatments', 'procedure')) {
            $existing = DB::table('treatments')
                ->select('id', 'procedure', 'cost', 'created_at', 'updated_at')
                ->whereNotNull('procedure')
                ->where('procedure', '<>', '')
                ->get();

            foreach ($existing as $row) {
                DB::table('treatment_procedures')->insert([
                    'treatment_id' => $row->id,
                    'name' => $row->procedure,
                    'cost' => $row->cost ?? 0,
                    'created_at' => $row->created_at ?? now(),
                    'updated_at' => $row->updated_at ?? now(),
                ]);
            }

            Schema::table('treatments', function (Blueprint $table) {
                $table->dropColumn('procedure');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('treatments', 'procedure')) {
            Schema::table('treatments', function (Blueprint $table) {
                $table->string('procedure')->nullable();
            });

            $procedures = DB::table('treatment_procedures')
                ->select('treatment_id', DB::raw('MIN(name) as name'))
                ->groupBy('treatment_id')
                ->get();

            foreach ($procedures as $procedure) {
                DB::table('treatments')
                    ->where('id', $procedure->treatment_id)
                    ->update(['procedure' => $procedure->name]);
            }
        }

        Schema::dropIfExists('treatment_procedures');
    }
};
