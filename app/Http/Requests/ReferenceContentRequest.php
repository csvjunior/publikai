<?php

namespace App\Http\Requests;

use App\Enums\ReferenceContentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validação de conteúdo de referência (create/update).
 * Quick flow: só URL (+ tipo) basta; análise detalhada é opcional.
 */
class ReferenceContentRequest extends FormRequest
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
            'url' => ['required', 'url', 'max:2048'],
            'title' => ['nullable', 'string', 'max:255'],
            'content_type' => ['nullable', Rule::in(array_keys(config('references.content_types')))],
            'observed_hook' => ['nullable', 'string', 'max:255'],
            'observed_structure' => ['nullable', 'string'],
            'observed_cta' => ['nullable', 'string', 'max:255'],
            'observed_style' => ['nullable', 'string', 'max:255'],
            'duration_seconds' => ['nullable', 'integer', 'min:0'],
            'performance_notes' => ['nullable', 'string'],
            'why_it_works' => ['nullable', 'string'],
            'status' => ['required', Rule::enum(ReferenceContentStatus::class)],
            'notes' => ['nullable', 'string'],
        ];
    }
}
