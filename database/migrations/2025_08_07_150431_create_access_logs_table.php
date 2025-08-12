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
        Schema::create('access_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('visit_pass_id')->nullable()->constrained('visit_passes');
            $table->string('code_entered');
            $table->string('event_type'); // e.g., 'ENTRY_ATTEMPT', 'EXIT'
            $table->string('status'); // e.g., 'SUCCESS', 'FAILED_EXPIRED', 'FAILED_NOT_FOUND'
            $table->text('remarks')->nullable(); // Observaciones del vigilante
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('access_logs');
    }
};
