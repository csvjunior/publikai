<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Referências visuais múltiplas do Avatar (Sprint 5.5.3).
     * Pivot com primary única (transação) + position estável. Migra a
     * referência singular da 5.5.2 (primary, position 1) e remove a coluna.
     * Não edita migrations aplicadas.
     */
    public function up(): void
    {
        Schema::create('avatar_reference_media_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('avatar_id')->constrained()->cascadeOnDelete();
            $table->foreignId('media_asset_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->unsignedInteger('position')->default(1);
            $table->timestamps();

            $table->unique(['avatar_id', 'media_asset_id'], 'avatar_ref_asset_unique');
            $table->index(['avatar_id', 'is_primary'], 'avatar_ref_primary_idx');
        });

        foreach (DB::table('avatars')->whereNotNull('reference_media_asset_id')->get(['id', 'reference_media_asset_id']) as $row) {
            DB::table('avatar_reference_media_assets')->insert([
                'avatar_id' => $row->id,
                'media_asset_id' => $row->reference_media_asset_id,
                'is_primary' => true,
                'position' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Schema::table('avatars', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reference_media_asset_id');
        });
    }

    public function down(): void
    {
        Schema::table('avatars', function (Blueprint $table) {
            $table->foreignId('reference_media_asset_id')->nullable()->after('status')
                ->constrained('media_assets')->nullOnDelete();
        });

        foreach (DB::table('avatar_reference_media_assets')->where('is_primary', true)->orderBy('position')->get(['avatar_id', 'media_asset_id']) as $row) {
            DB::table('avatars')->where('id', $row->avatar_id)->update(['reference_media_asset_id' => $row->media_asset_id]);
        }

        Schema::dropIfExists('avatar_reference_media_assets');
    }
};
