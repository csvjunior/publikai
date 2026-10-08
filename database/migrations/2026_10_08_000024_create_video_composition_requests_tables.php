<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Composição local de vídeo (Sprint 5.6.1, FFmpeg).
     * Request + inputs ordenados com snapshot (trim/duração). Sem
     * provider/model (composição local, não IA). Não edita aplicadas.
     */
    public function up(): void
    {
        Schema::create('video_composition_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_script_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('output_media_asset_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->string('status', 20)->default('pending');
            $table->string('error_code', 50)->nullable();
            $table->text('error_message')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at'], 'vcomp_req_status_idx');
        });

        Schema::create('video_composition_inputs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('video_composition_request_id', 'vcomp_in_request_fk')->constrained('video_composition_requests')->cascadeOnDelete();
            $table->foreignId('media_asset_id', 'vcomp_in_asset_fk')->constrained('media_assets')->cascadeOnDelete();
            $table->unsignedInteger('position');
            $table->unsignedBigInteger('trim_start_ms')->nullable();
            $table->unsignedBigInteger('trim_end_ms')->nullable();
            $table->unsignedInteger('image_duration_ms')->nullable();
            $table->timestamps();

            $table->unique(['video_composition_request_id', 'position'], 'vcomp_in_position_unique');
            $table->unique(['video_composition_request_id', 'media_asset_id'], 'vcomp_in_asset_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_composition_inputs');
        Schema::dropIfExists('video_composition_requests');
    }
};
