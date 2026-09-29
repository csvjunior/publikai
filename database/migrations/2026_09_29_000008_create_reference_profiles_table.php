<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Perfis/canais externos usados como referência (Sprint 4).
     * Apenas cadastro e organização manual — sem scraping, sem IA.
     */
    public function up(): void
    {
        Schema::create('reference_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('platform', 20);
            $table->string('username', 100)->nullable();
            $table->string('profile_url', 2048);
            $table->string('language', 10)->nullable();
            $table->string('market', 10)->nullable();
            $table->string('niche')->nullable();
            $table->text('reason')->nullable();
            $table->string('status', 20)->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('platform');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reference_profiles');
    }
};
