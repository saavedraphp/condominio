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
        Schema::create('visit_passes', function (Blueprint $table) {
            $table->id();
            $table->morphs('creator');
            $table->foreignId('house_id')->constrained('houses');
            $table->string('title');
            $table->text('details')->nullable();
            $table->dateTime('starts_at');
            $table->dateTime('expires_at');
            $table->string('access_code')->unique();
            $table->boolean('is_locked')->default(false);
            $table->bigInteger('white_label_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visit_passes');
    }
};
