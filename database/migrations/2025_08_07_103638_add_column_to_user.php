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
        Schema::table('users', function (Blueprint $table) {
            $table->bigInteger('document_type_id')->nullable()->after('phone');
            $table->string('document_number')->nullable()->after('document_type_id');
            $table->string('file_path')->nullable()->after('document_number');
            $table->string('public_access_token')->nullable()->after('file_path');
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['file_path', 'public_access_token', 'document_type_id', 'document_number']);
            $table->dropSoftDeletes();
        });
    }
};
