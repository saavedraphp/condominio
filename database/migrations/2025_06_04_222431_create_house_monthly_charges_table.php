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
        Schema::create('house_monthly_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('house_id')->constrained()->onDelete('cascade');
            $table->integer('period_year');
            $table->integer('period_month');
            $table->decimal('total_amount', 10, 2)->default(0.00);
            $table->enum('status', ['draft', 'issued', 'paid', 'partially_paid', 'overdue', 'cancelled'])->default('draft');
            $table->date('issued_date')->nullable();
            $table->date('due_date')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['house_id', 'period_year', 'period_month', 'deleted_at'], 'house_period_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('house_monthly_charges');
    }
};
