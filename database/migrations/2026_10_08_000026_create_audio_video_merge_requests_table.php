<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Merge local vídeo + narração (Sprint 5.6.3, FFmpeg).
     * Snapshot dos inputs; output ligado após success. Política de duração
     * explícita (video_master). Não edita migrations aplicadas.
     */
    public function up(): void
    {
        Schema::create('audio_video_merge_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_script_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('video_media_asset_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->foreignId('audio_media_asset_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->foreignId('output_media_asset_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->string('status', 20)->default('pending');
            $table->string('duration_policy', 20)->default('video_master');
            $table->string('error_code', 50)->nullable();
            $table->text('error_message')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at'], 'avmerge_req_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audio_video_merge_requests');
    }
};
