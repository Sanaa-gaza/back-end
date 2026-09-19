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
        Schema::create('account_reviews', function (Blueprint $table) {
            $table->id();
            $table->string('account_type');
            $table->unsignedBigInteger('account_id');
            $table->foreignId('admin_id')->constrained('users');
            $table->enum('decision', ['approved', 'rejected', 'suspended', 'activated']);
            $table->string('reason')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['account_type', 'account_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_reviews');
    }
};
