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
        Schema::table('details_other_expenses', function (Blueprint $table) {
            $table->dropColumn(['title', 'description', 'date', 'amount']);
            $table->string('original_filename')->nullable()->after('file_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('details_other_expenses', function (Blueprint $table) {
            $table->string('title')->nullable()->after('other_expense_id');
            $table->string('description')->nullable()->after('title');
            $table->date('date')->nullable()->after('description');
            $table->decimal('amount', 15, 2)->nullable()->after('date');
            $table->dropColumn('original_filename');
        });
    }
};
