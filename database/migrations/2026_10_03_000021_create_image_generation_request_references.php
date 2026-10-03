<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Snapshot múltiplo de referências por geração (Sprint 5.5.3).
     * Relation table (integridade + ordenação) em vez de IDs em JSON.
     * Migra o snapshot singular da 5.5.2 e remove a coluna.
     * Não edita migrations aplicadas.
     */
    public function up(): void
    {
        Schema::create('image_generation_request_references', function (Blueprint $table) {
            $table->id();
            $table->foreignId('image_generation_request_id')->constrained('image_generation_requests', 'id', 'igr_ref_request_fk')->cascadeOnDelete();
            $table->foreignId('media_asset_id')->constrained('media_assets', 'id', 'igr_ref_asset_fk')->cascadeOnDelete();
            $table->unsignedInteger('position')->default(1);
            $table->timestamp('created_at')->nullable();

            $table->unique(['image_generation_request_id', 'media_asset_id'], 'igr_ref_pair_unique');
            $table->index(['image_generation_request_id', 'position'], 'igr_ref_order_idx');
        });

        foreach (DB::table('image_generation_requests')->whereNotNull('reference_media_asset_id')->get(['id', 'reference_media_asset_id']) as $row) {
            DB::table('image_generation_request_references')->insert([
                'image_generation_request_id' => $row->id,
                'media_asset_id' => $row->reference_media_asset_id,
                'position' => 1,
                'created_at' => now(),
            ]);
        }

        Schema::table('image_generation_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reference_media_asset_id');
        });
    }

    public function down(): void
    {
        Schema::table('image_generation_requests', function (Blueprint $table) {
            $table->foreignId('reference_media_asset_id')->nullable()->after('content_script_id')
                ->constrained('media_assets')->nullOnDelete();
        });

        foreach (DB::table('image_generation_request_references')->orderBy('position')->get(['image_generation_request_id', 'media_asset_id']) as $row) {
            DB::table('image_generation_requests')->where('id', $row->image_generation_request_id)->update(['reference_media_asset_id' => $row->media_asset_id]);
        }

        Schema::dropIfExists('image_generation_request_references');
    }
};
