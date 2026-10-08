<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Geração de vídeo image-to-video (Sprint 5.6.0, Veo).
     * Lifecycle próprio (operação externa + polling): tabela dedicada em
     * vez de reutilizar image requests. Prompt funcional p/ o Job.
     * Não edita migrations aplicadas.
     */
    public function up(): void
    {
        Schema::create('video_generation_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_script_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('source_media_asset_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->foreignId('media_asset_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 20)->default('pending');
            $table->string('provider', 20)->nullable();
            $table->string('model')->nullable();
            $table->text('prompt');
            $table->string('aspect_ratio', 10)->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->string('operation_external_id')->nullable();
            $table->string('error_code', 50)->nullable();
            $table->text('error_message')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at'], 'video_req_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_generation_requests');
    }
};
