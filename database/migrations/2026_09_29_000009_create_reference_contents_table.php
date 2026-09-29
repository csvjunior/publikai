<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Conteúdos específicos de um perfil de referência (Sprint 4).
     * Observações manuais (hook, estrutura, CTA, estilo, performance):
     * base estruturada para futura análise por IA. Sem embeddings,
     * sem scores, sem JSON de IA.
     */
    public function up(): void
    {
        Schema::create('reference_contents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reference_profile_id')->constrained()->cascadeOnDelete();
            $table->string('url', 2048);
            $table->string('title')->nullable();
            $table->string('content_type', 30)->nullable();
            $table->string('observed_hook')->nullable();
            $table->text('observed_structure')->nullable();
            $table->string('observed_cta')->nullable();
            $table->string('observed_style')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->text('performance_notes')->nullable();
            $table->text('why_it_works')->nullable();
            $table->string('status', 20)->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['reference_profile_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reference_contents');
    }
};
