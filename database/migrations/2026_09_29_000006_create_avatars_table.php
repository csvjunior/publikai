<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Avatares: identidades visuais reutilizáveis (Sprint 3).
     * ethnicity_description é descrição visual opcional manual para
     * consistência de personagem artificial — sem inferência ou
     * classificação automática. Sem upload de imagem nesta Sprint
     * (apenas reference_notes como instrução futura).
     */
    public function up(): void
    {
        Schema::create('avatars', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('apparent_age', 20)->nullable();
            $table->string('gender_presentation')->nullable();
            $table->string('ethnicity_description')->nullable();
            $table->string('hair')->nullable();
            $table->string('eyes')->nullable();
            $table->string('skin')->nullable();
            $table->string('body_description')->nullable();
            $table->string('default_clothing')->nullable();
            $table->string('visual_style')->nullable();
            $table->string('preferred_scenarios')->nullable();
            $table->string('voice_description')->nullable();
            $table->string('language', 10)->nullable();
            $table->string('market', 10)->nullable();
            $table->text('reference_notes')->nullable();
            $table->string('status', 20)->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avatars');
    }
};
