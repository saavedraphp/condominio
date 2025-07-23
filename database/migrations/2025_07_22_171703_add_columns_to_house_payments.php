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
        Schema::table('house_payments', function (Blueprint $table) {
            $table->string('transaction_code')->nullable()->after('amount');
            $table->date('payment_date')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('house_payments', function (Blueprint $table) {
            $table->dateTime('payment_date')->nullable()->change();
            $table->dropColumn('transaction_code');
        });
    }
};
