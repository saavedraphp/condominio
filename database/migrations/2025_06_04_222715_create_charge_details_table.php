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
        Schema::create('charge_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('house_monthly_charge_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('parent_detail_id')->nullable();
            $table->foreign('parent_detail_id')
                ->references('id')
                ->on('charge_details')
                ->onDelete('cascade');
            $table->string('item_description', 255);
            $table->decimal('amount', 10, 2);
            $table->string('item_type_code', 50)->nullable()->index(); // ej: WATER_CONSUMPTION, JP_ISLA_CERDENA_FEE_TOTAL
            $table->json('calculation_snapshot')->nullable(); // Para almacenar cómo se calculó
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('charge_details');
    }
};
