<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Produção coordenada de conteúdo (Sprint 5.6.5, orchestrator).
     * UMA ação do usuário, várias etapas internas, reuse-first.
     * Não edita migrations aplicadas.
     */
    public function up(): void
    {
        Schema::create('content_productions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_script_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('pending');
            $table->string('current_step', 20)->default('preparing');
            $table->boolean('force_new')->default(false);
            $table->foreignId('image_media_asset_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->foreignId('video_media_asset_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->foreignId('audio_media_asset_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->foreignId('final_media_asset_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->string('error_code', 50)->nullable();
            $table->text('error_message')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['content_script_id', 'status'], 'content_prod_script_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_productions');
    }
};
