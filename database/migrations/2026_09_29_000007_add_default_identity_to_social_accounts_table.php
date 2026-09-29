<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Identidade padrão de conteúdo da conta social (Sprint 3).
     * Persona/Avatar reutilizáveis por várias contas (belongsTo, sem N:N).
     * nullOnDelete: sem delete físico nesta Sprint, mas coerente se um dia
     * existir — a conta apenas perde a referência, sem apagar histórico.
     */
    public function up(): void
    {
        Schema::table('social_accounts', function (Blueprint $table) {
            $table->foreignId('default_persona_id')->nullable()->after('posting_frequency')
                ->constrained('personas')->nullOnDelete();
            $table->foreignId('default_avatar_id')->nullable()->after('default_persona_id')
                ->constrained('avatars')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('social_accounts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('default_avatar_id');
            $table->dropConstrainedForeignId('default_persona_id');
        });
    }
};
