<?php

// database/migrations/xxxx_xx_xx_xxxxxx_create_petty_cash_funds_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('petty_cash_funds', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('white_label_id'); // Assuming you have this
            $table->date('opening_date');
            $table->decimal('opening_balance', 15, 2);
            $table->string('responsible_person')->nullable();
            $table->text('description')->nullable(); // Descripción/Notas del fondo
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->date('closing_date')->nullable();
            $table->decimal('counted_closing_balance', 15, 2)->nullable(); // Saldo contado al cierre
            // Este campo es importante para calcular la diferencia: Saldo teórico en el momento del cierre.
            // Podría ser calculado y guardado al cerrar, o calculado dinámicamente si se necesita.
            // Por simplicidad, lo guardaremos al momento del cierre.
            $table->decimal('calculated_balance_at_closing', 15, 2)->nullable();
            $table->timestamps();

            // $table->foreign('white_label_id')->references('id')->on('white_labels'); // If you have a white_labels table
            // Considerar un user_id si el responsable es un usuario del sistema
            // $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('petty_cash_funds');
    }
};
