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
        Schema::create('houses', function (Blueprint $table) {
            $table->id();
            $table->string('payment_code',10)->nullable();
            $table->string('property_unit')->nullable();
            $table->string('address')->nullable();
            $table->smallInteger('construction_area',false,false)->nullable();
            $table->decimal('participation_percentage')->nullable();
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
        Schema::dropIfExists('houses');
    }
};
