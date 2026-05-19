<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AprovarDisciplinaSecretariaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('Admin') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'disciplina' => ['required', 'string', Rule::in(['obrigatoria', 'opcional_1', 'opcional_2'])],
        ];
    }
}
