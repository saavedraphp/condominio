<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('petty_cash_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('white_label_id');
            $table->foreignId('petty_cash_fund_id')->constrained('petty_cash_funds')->onDelete('cascade');
            $table->date('transaction_date');
            $table->string('description');
            $table->enum('type', ['income', 'expense']);
            $table->decimal('amount', 15, 2);
            $table->string('file_path')->nullable(); // Ruta al archivo adjunto
            $table->timestamps();

            // $table->foreign('white_label_id')->references('id')->on('white_labels');
        });
    }

    public function down()
    {
        Schema::dropIfExists('petty_cash_transactions');
    }
};
