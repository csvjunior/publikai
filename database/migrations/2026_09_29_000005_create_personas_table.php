<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Personas: identidades de comunicação reutilizáveis (Sprint 3).
     * Apenas cadastro e organização — sem interpretação por IA.
     */
    public function up(): void
    {
        Schema::create('personas', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('language', 10)->nullable();
            $table->string('market', 10)->nullable();
            $table->string('audience')->nullable();
            $table->string('personality')->nullable();
            $table->string('tone')->nullable();
            $table->string('communication_style')->nullable();
            $table->string('vocabulary')->nullable();
            $table->text('expressions')->nullable();
            $table->text('content_preferences')->nullable();
            $table->text('avoidances')->nullable();
            $table->string('default_cta_style')->nullable();
            $table->string('status', 20)->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personas');
    }
};
