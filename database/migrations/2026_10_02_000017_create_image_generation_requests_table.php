<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Solicitações de geração de imagem (Sprint 5.5.0 async).
     * Representa a execução enquanto não há MediaAsset. O prompt é dado
     * funcional necessário ao Job (diferente de ai_generations, que nunca
     * guarda prompts): não vai para logs nem para metadata de ai_generations.
     */
    public function up(): void
    {
        Schema::create('image_generation_requests', function (Blueprint $table) {
            $table->id();
            $table->string('status', 20)->default('pending');
            $table->text('prompt');
            $table->string('aspect_ratio', 10)->nullable();
            $table->string('image_size', 10)->nullable();
            $table->string('mime_type', 50)->nullable();
            $table->string('provider', 20)->nullable();
            $table->string('model')->nullable();
            $table->foreignId('media_asset_id')->nullable()->constrained()->nullOnDelete();
            $table->string('error_code', 50)->nullable();
            $table->text('error_message')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('image_generation_requests');
    }
};
