<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Contas sociais gerenciadas pelo Publikai (Sprint 2, cadastro manual).
     *
     * Decisão de unicidade: unique(platform, username) — o mesmo nome pode
     * existir em redes diferentes, mas não duplicado dentro da mesma
     * plataforma (collation padrão case-insensitive cobre variações de caixa).
     * Sem tokens OAuth, sem integrações: apenas dados operacionais (DNA).
     */
    public function up(): void
    {
        Schema::create('social_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('platform', 20);
            $table->string('username');
            $table->string('profile_url', 2048)->nullable();
            $table->string('language', 10)->nullable();
            $table->string('market', 10)->nullable();
            $table->string('niche')->nullable();
            $table->string('audience')->nullable();
            $table->string('tone')->nullable();
            $table->string('content_style')->nullable();
            $table->string('default_cta')->nullable();
            $table->string('posting_frequency')->nullable();
            $table->string('status', 20)->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['platform', 'username']);
            $table->index('platform');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_accounts');
    }
};
