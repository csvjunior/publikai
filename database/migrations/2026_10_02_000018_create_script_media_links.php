<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Vínculo Script ↔ Assets + contexto da geração (Sprint 5.5.1).
     * Um Script pode ter várias imagens (tentativas, versões).
     * Não edita migrations aplicadas.
     */
    public function up(): void
    {
        Schema::create('content_script_media_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_script_id')->constrained()->cascadeOnDelete();
            $table->foreignId('media_asset_id')->constrained()->cascadeOnDelete();
            $table->string('purpose', 20)->default('scene');
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique(['content_script_id', 'media_asset_id'], 'script_asset_unique');
            $table->index(['content_script_id', 'is_primary'], 'script_asset_primary_idx');
        });

        Schema::table('image_generation_requests', function (Blueprint $table) {
            $table->foreignId('content_script_id')->nullable()->after('model')
                ->constrained()->nullOnDelete();
            $table->string('purpose', 20)->nullable()->after('content_script_id');
            $table->boolean('is_primary')->default(false)->after('purpose');
        });
    }

    public function down(): void
    {
        Schema::table('image_generation_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('content_script_id');
            $table->dropColumn(['purpose', 'is_primary']);
        });

        Schema::dropIfExists('content_script_media_assets');
    }
};
