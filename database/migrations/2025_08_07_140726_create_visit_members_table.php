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
        Schema::create('visit_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_pass_id')->constrained('visit_passes');
            $table->string('first_name');
            $table->string('last_name');
            $table->bigInteger('document_type_id')->nullable();
            $table->string('document_number')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visit_members');
    }
};
