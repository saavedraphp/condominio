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
        Schema::table('vehicles', function (Blueprint $table) {

            // A. Eliminamos la clave foránea. Como la tabla aún se llama 'vehicles',
            // Laravel buscará y encontrará 'vehicles_web_user_id_foreign'.
            $table->dropForeign(['web_user_id']);

            // B. Eliminamos la columna.
            $table->dropColumn('web_user_id');

            // C. Añadimos la nueva columna y su clave foránea.
            $table->foreignId('house_id')
                ->nullable()
                ->after('id')
                ->constrained('houses')
                ->onDelete('set null');
        });

        // 2. AHORA, como último paso, renombramos la tabla.
        Schema::rename('vehicles', 'house_vehicles');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('house_vehicles', 'vehicles');

        // 2. Ahora, modificamos la tabla con su nombre ya restaurado ('vehicles').
        Schema::table('vehicles', function (Blueprint $table) {
            // A. Eliminamos la columna y la clave foránea que añadimos en up().
            $table->dropForeign(['house_id']);
            $table->dropColumn('house_id');

            // B. Recreamos la columna y la clave foránea que eliminamos en up().
            $table->foreignId('web_user_id')->nullable()->constrained('web_users');
        });


    }
};
