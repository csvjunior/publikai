<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Análises por IA de um perfil de referência (Sprint 5.1).
     * Histórico imutável: cada execução cria um registro; nunca sobrescreve.
     * Campos estruturados em JSON; sem resposta bruta, sem prompts.
     */
    public function up(): void
    {
        Schema::create('reference_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reference_profile_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('pending');
            $table->string('provider', 20)->nullable();
            $table->string('model')->nullable();
            $table->text('summary')->nullable();
            $table->json('dominant_hooks')->nullable();
            $table->json('content_structures')->nullable();
            $table->json('cta_patterns')->nullable();
            $table->json('visual_patterns')->nullable();
            $table->json('communication_patterns')->nullable();
            $table->json('audience_signals')->nullable();
            $table->json('content_angles')->nullable();
            $table->json('repeated_patterns')->nullable();
            $table->json('risks')->nullable();
            $table->json('recommendations')->nullable();
            $table->decimal('confidence', 3, 2)->nullable();
            $table->string('error_code', 50)->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['reference_profile_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reference_analyses');
    }
};
