<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReprovarInscricaoSecretariaRequest extends FormRequest
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
            'disciplina' => ['nullable', 'string', Rule::in(['obrigatoria', 'opcional_1', 'opcional_2'])],
            'justificativa' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'justificativa.max' => 'A justificativa da reprovação deve ter no máximo 5000 caracteres.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('justificativa')) {
            $this->merge([
                'justificativa' => $this->string('justificativa')->trim()->toString(),
            ]);
        }
    }
}
