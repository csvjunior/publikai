<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Log de gerações de IA (Sprint 5.0): observabilidade e futuro custo.
     * Nunca armazena credenciais, headers de autenticação, segredos ou
     * prompts completos — apenas metadados sanitizados.
     */
    public function up(): void
    {
        Schema::create('ai_generations', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 20);
            $table->string('model');
            $table->string('operation', 50);
            $table->string('status', 20)->default('pending');
            $table->unsignedBigInteger('input_tokens')->nullable();
            $table->unsignedBigInteger('output_tokens')->nullable();
            $table->decimal('estimated_cost_usd', 10, 6)->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->string('external_request_id')->nullable();
            $table->string('error_code', 50)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['provider', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_generations');
    }
};
