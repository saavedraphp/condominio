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
        Schema::create('annual_budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_type_id')->constrained('budget_types')->onDelete('cascade');
            $table->year('year');
            $table->decimal('amount', 15, 2);
            $table->unsignedBigInteger('white_label_id')->index();
            $table->timestamps();
            $table->unique(['budget_type_id', 'year', 'white_label_id'], 'annual_budget_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('annual_budgets');
    }
};
