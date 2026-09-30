<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Revisão humana da proposta (create/update de Persona/Avatar finais).
 * Reutiliza as regras dos Requests de Persona/Avatar com prefixos
 * persona.* e avatar.* (sem status — proposta não decide status).
 */
class UpdateIdentityProposalRequest extends FormRequest
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
        $rules = [];

        foreach ((new StorePersonaRequest)->rules() as $field => $rule) {
            if ($field !== 'status') {
                $rules['persona.'.$field] = $rule;
            }
        }

        foreach ((new StoreAvatarRequest)->rules() as $field => $rule) {
            if ($field !== 'status') {
                $rules['avatar.'.$field] = $rule;
            }
        }

        return $rules;
    }
}
