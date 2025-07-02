<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('budget_types', function (Blueprint $table) {
            $table->enum('budget_scope', ['building', 'association'])
                ->default('association')
                ->after('name');
            $table->softDeletes()->after('white_label_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('budget_types', function (Blueprint $table) {
            $table->dropColumn('budget_scope');
            $table->dropSoftDeletes();
        });
    }
};
