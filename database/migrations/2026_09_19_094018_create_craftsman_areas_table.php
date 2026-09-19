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
        Schema::create('craftsman_areas', function (Blueprint $table) {
            $table->foreignId('craftsman_id')->constrained('craftsmen')->cascadeOnDelete();
            $table->foreignId('area_id')->constrained('areas');

            $table->primary(['craftsman_id', 'area_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('craftsman_areas');
    }
};
