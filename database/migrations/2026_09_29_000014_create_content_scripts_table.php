<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Roteiros textuais estruturados (Sprint 5.4): propostas manuais ou por IA.
     * Não é mídia final, publicação ou Campaign. CTA textual, sem links.
     */
    public function up(): void
    {
        Schema::create('content_scripts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('content_blueprint_id')->constrained()->cascadeOnDelete();
            $table->foreignId('persona_id')->constrained()->cascadeOnDelete();
            $table->foreignId('avatar_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('status', 20)->default('draft');
            $table->string('language', 10)->nullable();
            $table->string('market', 10)->nullable();
            $table->string('objective')->nullable();
            $table->text('hook');
            $table->text('opening')->nullable();
            $table->text('body');
            $table->text('cta');
            $table->text('on_screen_text')->nullable();
            $table->text('visual_direction')->nullable();
            $table->text('voice_direction')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->string('generation_source', 20)->default('manual');
            $table->string('provider', 20)->nullable();
            $table->string('model')->nullable();
            $table->string('error_code', 50)->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_scripts');
    }
};
