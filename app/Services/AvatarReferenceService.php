<?php

namespace App\Services;

use App\Enums\MediaAssetSource;
use App\Enums\MediaAssetStatus;
use App\Enums\MediaAssetType;
use App\Models\Avatar;
use App\Models\MediaAsset;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Referências visuais do Avatar (Sprints 5.5.2/5.5.3).
 * Upload manual → MediaAssets `uploaded` → pivot com primary única e
 * position estável. As imagens são só auxílio de consistência visual do
 * personagem artificial: sem identificação, biometria ou inferência de
 * atributos sensíveis. Asset exclusivamente-referência é removido com
 * segurança; compartilhado com outro vínculo nunca é apagado
 * automaticamente.
 */
class AvatarReferenceService
{
    /**
     * @var string[]
     */
    private const ALLOWED_MIMES = ['image/jpeg', 'image/png'];

    public function maxReferences(): int
    {
        return max(1, (int) config('ai.google.image.max_references', 4));
    }

    /**
     * Adiciona uma referência (primeira vira primary automaticamente).
     *
     * @throws ValidationException
     */
    public function attach(Avatar $avatar, UploadedFile $file, ?int $userId = null): MediaAsset
    {
        if ($avatar->referenceImages()->count() >= $this->maxReferences()) {
            throw ValidationException::withMessages([
                'image' => 'Limite de referências atingido.',
            ]);
        }

        return DB::transaction(function () use ($avatar, $file, $userId) {
            $asset = $this->store($file, $userId);

            $isFirst = ! $avatar->referenceImages()->exists();
            $position = ((int) $avatar->referenceImages()->max('avatar_reference_media_assets.position')) + 1;

            $avatar->referenceImages()->attach($asset->id, [
                'is_primary' => $isFirst,
                'position' => $position,
            ]);

            return $asset;
        });
    }

    /**
     * Remove uma referência do Avatar, preservando o Avatar.
     * Se era primary e restam outras, promove a primeira por position.
     */
    public function remove(Avatar $avatar, MediaAsset $asset): void
    {
        abort_unless(
            $avatar->referenceImages()->whereKey($asset->id)->exists(),
            404
        );

        $wasPrimary = (bool) $avatar->referenceImages()->whereKey($asset->id)->first()?->pivot->is_primary;

        DB::transaction(function () use ($avatar, $asset, $wasPrimary) {
            $avatar->referenceImages()->detach($asset->id);

            if ($wasPrimary) {
                $next = $avatar->referenceImages()->first();

                if ($next) {
                    $avatar->referenceImages()->updateExistingPivot($next->id, ['is_primary' => true]);
                }
            }
        });

        $this->deleteIfExclusive($asset->id);
    }

    /**
     * Define a primary do Avatar (transação; anterior desmarcada).
     */
    public function markPrimary(Avatar $avatar, MediaAsset $asset): void
    {
        abort_unless(
            $avatar->referenceImages()->whereKey($asset->id)->exists(),
            404
        );

        DB::transaction(function () use ($avatar, $asset) {
            DB::table('avatar_reference_media_assets')
                ->where('avatar_id', $avatar->id)
                ->update(['is_primary' => false]);

            $avatar->referenceImages()->updateExistingPivot($asset->id, ['is_primary' => true]);
        });
    }

    /**
     * Persiste o upload como MediaAsset `uploaded` (UUID, path relativo).
     * Validação binária real: MIME + imagem decodificável + dimensões.
     */
    protected function store(UploadedFile $file, ?int $userId): MediaAsset
    {
        $binary = file_get_contents($file->getRealPath());
        $info = $binary === false ? false : @getimagesizefromstring($binary);

        abort_unless(
            $info !== false && in_array($info['mime'] ?? null, self::ALLOWED_MIMES, true),
            422,
            'O arquivo não é uma imagem JPEG/PNG válida.'
        );

        $mime = $info['mime'];
        $path = 'avatars/references/'.now()->format('Y/m').'/'.Str::uuid().($mime === 'image/png' ? '.png' : '.jpg');

        Storage::disk('public')->put($path, $binary);

        return MediaAsset::create([
            'type' => MediaAssetType::Image,
            'source' => MediaAssetSource::Uploaded,
            'provider' => null,
            'model' => null,
            'disk' => 'public',
            'path' => $path,
            'filename' => basename($path),
            'mime_type' => $mime,
            'width' => $info[0] ?: null,
            'height' => $info[1] ?: null,
            'size_bytes' => strlen($binary),
            'aspect_ratio' => null,
            'status' => MediaAssetStatus::Ready,
            'created_by' => $userId,
        ]);
    }

    /**
     * Apaga arquivo + linha só quando nenhum outro vínculo usa o asset:
     * outro Avatar, snapshot de request, saída ou vínculo de roteiro.
     */
    protected function deleteIfExclusive(int $mediaAssetId): void
    {
        $shared = DB::table('avatar_reference_media_assets')
            ->where('media_asset_id', $mediaAssetId)
            ->exists()
            || DB::table('image_generation_request_references')
                ->where('media_asset_id', $mediaAssetId)
                ->exists()
            || DB::table('image_generation_requests')
                ->where('media_asset_id', $mediaAssetId)
                ->exists()
            || DB::table('content_script_media_assets')
                ->where('media_asset_id', $mediaAssetId)
                ->exists();

        if ($shared) {
            return;
        }

        $asset = MediaAsset::find($mediaAssetId);

        if (! $asset) {
            return;
        }

        Storage::disk($asset->disk)->delete($asset->path);
        $asset->delete();
    }
}
