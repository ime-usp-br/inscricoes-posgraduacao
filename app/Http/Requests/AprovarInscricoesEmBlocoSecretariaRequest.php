<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AprovarInscricoesEmBlocoSecretariaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAccessSecretaria() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'inscricoes' => ['required', 'array', 'min:1'],
            'inscricoes.*' => ['required', 'integer', 'exists:inscricaos,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'inscricoes.required' => 'Selecione ao menos uma inscrição.',
            'inscricoes.min' => 'Selecione ao menos uma inscrição.',
        ];
    }
}
