<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Blueprints de conteúdo (Sprint 5.3): ESTRUTURAS reutilizáveis, não
     * conteúdo final. Sem roteiro, sem mídia. source_type prepara o futuro
     * AI-assisted; manual é o único fluxo real nesta Sprint.
     */
    public function up(): void
    {
        Schema::create('content_blueprints', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('content_type', 30)->nullable();
            $table->string('objective')->nullable();
            $table->string('hook_pattern')->nullable();
            $table->text('structure_pattern')->nullable();
            $table->string('cta_pattern')->nullable();
            $table->string('visual_style')->nullable();
            $table->string('communication_style')->nullable();
            $table->unsignedInteger('recommended_duration_seconds')->nullable();
            $table->string('language', 10)->nullable();
            $table->string('market', 10)->nullable();
            $table->string('niche')->nullable();
            $table->string('status', 20)->default('active');
            $table->string('source_type', 20)->default('manual');
            $table->foreignId('source_reference_profile_id')->nullable()->constrained('reference_profiles')->nullOnDelete();
            $table->foreignId('source_reference_analysis_id')->nullable()->constrained('reference_analyses')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('content_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_blueprints');
    }
};
