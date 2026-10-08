<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Narração por voz/TTS (Sprint 5.6.2).
     * Texto funcional p/ o Job (fora de logs e ai_generations).
     * Não edita migrations aplicadas.
     */
    public function up(): void
    {
        Schema::create('audio_generation_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_script_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('media_asset_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 20)->default('pending');
            $table->string('provider', 20)->nullable();
            $table->string('model')->nullable();
            $table->string('voice', 50)->nullable();
            $table->string('language', 10)->nullable();
            $table->string('style')->nullable();
            $table->text('text');
            $table->string('error_code', 50)->nullable();
            $table->text('error_message')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at'], 'audio_req_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audio_generation_requests');
    }
};
