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
        Schema::table('institutions', function (Blueprint $table) {
            $table->dropForeign(['activity_type_id']);
            $table->dropColumn('activity_type_id');
            $table->string('activity_type')->after('commercial_registration_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('institutions', function (Blueprint $table) {
            $table->dropColumn('activity_type');
            $table->foreignId('activity_type_id')->after('commercial_registration_number')->constrained('activity_types');
        });
    }
};
