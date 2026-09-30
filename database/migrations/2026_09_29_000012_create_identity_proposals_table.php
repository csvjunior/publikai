<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Propostas de identidade por IA (Sprint 5.2): Persona + Avatar sugeridos
     * a partir de análise concluída. Human-in-the-loop obrigatório — nada é
     * salvo nas tabelas finais sem revisão e apply explícitos.
     */
    public function up(): void
    {
        Schema::create('identity_proposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reference_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reference_analysis_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 20)->default('pending');
            $table->string('provider', 20)->nullable();
            $table->string('model')->nullable();
            $table->json('persona_data')->nullable();
            $table->json('avatar_data')->nullable();
            $table->json('rationale')->nullable();
            $table->string('error_code', 50)->nullable();
            $table->text('error_message')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('applied_persona_id')->nullable()->constrained('personas')->nullOnDelete();
            $table->foreignId('applied_avatar_id')->nullable()->constrained('avatars')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();

            $table->index(['reference_profile_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('identity_proposals');
    }
};
