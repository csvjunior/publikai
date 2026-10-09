<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Vínculo opcional com produção coordenada (Sprint 5.6.5).
     * Nullable + nullOnDelete; flows técnicos seguem funcionando sozinhos.
     */
    public function up(): void
    {
        foreach (['image_generation_requests', 'video_generation_requests', 'audio_generation_requests', 'audio_video_merge_requests'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->foreignId('content_production_id')->nullable()->after('content_script_id')
                    ->constrained('content_productions')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (['audio_video_merge_requests', 'audio_generation_requests', 'video_generation_requests', 'image_generation_requests'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropConstrainedForeignId('content_production_id');
            });
        }
    }
};
