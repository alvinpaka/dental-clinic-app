<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('odontogram_teeth', function (Blueprint $table) {
            $table->id();
            $table->foreignId('odontogram_id')->constrained('odontograms')->cascadeOnDelete();
            $table->string('tooth_code'); // e.g., FDI 11, 12, ...
            $table->enum('status', [
                'healthy','caries','restored','missing','crown','implant','root_canal','fracture','mobility','calculus'
            ])->default('healthy');
            $table->string('note')->nullable();
            $table->timestamps();

            $table->unique(['odontogram_id', 'tooth_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('odontogram_teeth');
    }
};
