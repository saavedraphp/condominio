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
        Schema::table('houses', function (Blueprint $table) {
            $table->enum('ownership_structure', [
                'owners_board',
                'association_only',
                'owners_board_with_association'
            ])
                ->after('participation_percentage')
                ->nullable()
                ->comment('Estructura de propiedad/gestión de la casa');

            $table->boolean('is_department')
                ->after('ownership_structure')
                ->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('houses', function (Blueprint $table) {
            $table->dropColumn('is_department');
            $table->dropColumn('ownership_structure');
        });
    }
};
