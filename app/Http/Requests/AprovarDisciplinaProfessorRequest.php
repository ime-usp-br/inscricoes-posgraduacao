<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AprovarDisciplinaProfessorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAccessProfessor() ?? false;
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
