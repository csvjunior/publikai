<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Assets de mídia (Sprint 5.5.0): entidade genérica p/ image/video/audio.
     * Nesta Sprint só image/ai_generated. Sem base64 no banco, sem segredos —
     * só caminho no Storage + metadata segura.
     */
    public function up(): void
    {
        Schema::create('media_assets', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20);
            $table->string('source', 20);
            $table->string('provider', 20)->nullable();
            $table->string('model')->nullable();
            $table->string('disk', 20)->default('public');
            $table->string('path');
            $table->string('filename');
            $table->string('mime_type', 50)->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->string('aspect_ratio', 10)->nullable();
            $table->string('status', 20)->default('ready');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_assets');
    }
};
