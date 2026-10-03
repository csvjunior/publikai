<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Referência visual do Avatar (Sprint 5.5.2).
     * Um MediaAsset ativo por Avatar + snapshot da referência usada em cada
     * request (auditabilidade: trocar a referência não altera gerações já
     * enfileiradas). Não edita migrations aplicadas.
     */
    public function up(): void
    {
        Schema::table('avatars', function (Blueprint $table) {
            $table->foreignId('reference_media_asset_id')->nullable()->after('status')
                ->constrained('media_assets')->nullOnDelete();
        });

        Schema::table('image_generation_requests', function (Blueprint $table) {
            $table->foreignId('reference_media_asset_id')->nullable()->after('content_script_id')
                ->constrained('media_assets')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('image_generation_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reference_media_asset_id');
        });

        Schema::table('avatars', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reference_media_asset_id');
        });
    }
};
