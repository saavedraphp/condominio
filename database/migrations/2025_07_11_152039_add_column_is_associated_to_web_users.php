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
        Schema::table('web_users', function (Blueprint $table) {
            $table->boolean('is_associated')->default(false)->after('has_payment_arrangement');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('web_users', function (Blueprint $table) {
            $table->dropColumn('is_associated');
        });
    }
};
