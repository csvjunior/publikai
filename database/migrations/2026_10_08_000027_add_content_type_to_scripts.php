<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tipo de conteúdo do roteiro (Sprint 5.6.4 refactor).
     * Justificativa: a intenção vídeo/imagem é conhecida no momento da
     * criação e não pode ser inferida com segurança depois (roteiro novo
     * de vídeo ainda não tem artefatos de vídeo). Nullable + fallback por
     * inferência para linhas antigas. Não toca migrations aplicadas.
     */
    public function up(): void
    {
        Schema::table('content_scripts', function (Blueprint $table) {
            $table->string('content_type', 10)->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('content_scripts', function (Blueprint $table) {
            $table->dropColumn('content_type');
        });
    }
};
