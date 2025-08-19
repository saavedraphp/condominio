<?php

// database/migrations/YYYY_MM_DD_HHMMSS_create_activity_logs_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\ActivityStatus; // Renombramos el Enum para que sea más genérico

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // El nombre de la tabla ahora es 'activity_logs'
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('qr_code_id')->nullable()->constrained('qr_codes')->nullOnDelete();
            $table->string('code');
            $table->string('status')->default(ActivityStatus::OK->value);
            $table->text('remarks')->nullable();
            $table->string('file_path')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
