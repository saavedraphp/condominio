<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->foreignId('chosen_quotation_id')
                ->nullable()
                ->after('white_label_id')
                ->constrained('quotations')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['chosen_quotation_id']);
            $table->dropColumn('chosen_quotation_id');
        });
    }
};
