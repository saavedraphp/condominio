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
        Schema::create('account_activations', function (Blueprint $table) {
            $table->id();
            $table->morphs('activatable'); // Crea activatable_id y activatable_type
            $table->string('token')->unique(); // Token único globalmente
            $table->timestamps();
            // El índice en (activatable_id, activatable_type) se crea automáticamente
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_activations');
    }
};
