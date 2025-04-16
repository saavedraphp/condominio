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
        Schema::create('house_web_user', function (Blueprint $table) {
            $table->foreignId('house_id')->constrained()->onDelete('cascade');
            $table->foreignId('web_user_id')->constrained()->onDelete('cascade');
            $table->boolean('is_owner');
            $table->boolean('is_resident');
            $table->boolean('is_manager');
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();
            $table->timestamps();
            $table->primary(['house_id', 'web_user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('house_web_user');
    }
};
