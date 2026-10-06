<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Edição controlada de imagem (Sprint 5.5.4).
     * Original imutável: variação cria NOVO asset com parent apontando a
     * origem; request guarda snapshot da source. Não edita aplicadas.
     */
    public function up(): void
    {
        Schema::table('media_assets', function (Blueprint $table) {
            $table->foreignId('parent_media_asset_id')->nullable()->after('status')
                ->constrained('media_assets')->nullOnDelete();
        });

        Schema::table('image_generation_requests', function (Blueprint $table) {
            $table->foreignId('source_media_asset_id')->nullable()->after('content_script_id')
                ->constrained('media_assets')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('image_generation_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('source_media_asset_id');
        });

        Schema::table('media_assets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_media_asset_id');
        });
    }
};
