<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Upload da imagem de referência do Avatar (Sprint 5.5.2).
 * JPEG/PNG reais, até 10 MB, mínimo 512×512, sem proporção imposta.
 */
class StoreAvatarReferenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'image' => [
                'required',
                'file',
                'image',
                'mimes:jpeg,jpg,png',
                'max:10240',
                'dimensions:min_width=512,min_height=512',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'image.dimensions' => 'A imagem deve ter ao menos 512×512 pixels.',
        ];
    }
}
