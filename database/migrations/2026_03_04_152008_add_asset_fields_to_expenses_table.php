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
        Schema::table('expenses', function (Blueprint $table) {
            $table->boolean('is_asset')->nullable()->after('file_path_job');
            $table->string('asset_type')->nullable()->after('is_asset');
            $table->string('asset_code', 10)->nullable()->after('asset_type');
            $table->string('asset_brand')->nullable()->after('asset_code');
            $table->decimal('market_value', 15, 2)->nullable()->after('asset_brand');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn([
                'is_asset',
                'asset_type',
                'asset_code',
                'asset_brand',
                'market_value'
            ]);
        });
    }
};
