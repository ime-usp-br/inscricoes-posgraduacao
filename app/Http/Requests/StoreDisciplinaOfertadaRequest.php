<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;

class StoreDisciplinaOfertadaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'professor_nome' => $this->filled('professor_nome') ? $this->input('professor_nome') : null,
            'professor_email' => $this->filled('professor_email') ? $this->input('professor_email') : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $periodoId = $this->integer('periodo_id');
        $departamento = $this->string('departamento')->toString();

        return [
            'periodo_id' => 'required|integer|exists:periodos,id',
            'departamento' => 'required|string|in:MAT,MAC,MAP,MAE,MPM',
            'codigo' => [
                'required',
                'string',
                'regex:/^\d{4}$/',
                Rule::unique('disciplinas_ofertadas', 'codigo')
                    ->where(fn (Builder $q) => $q
                        ->where('periodo_id', $periodoId)
                        ->where('departamento', $departamento)),
            ],
            'nome' => 'required|string|max:255',
            'professor_nome' => 'nullable|string|max:255',
            'professor_email' => 'nullable|email:rfc,dns|max:255',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'periodo_id.required' => 'O período é obrigatório.',
            'periodo_id.exists' => 'O período informado não existe.',
            'departamento.required' => 'O departamento é obrigatório.',
            'departamento.in' => 'O departamento deve ser MAT, MAC, MAP, MAE ou MPM.',
            'codigo.required' => 'O código numérico é obrigatório.',
            'codigo.regex' => 'O código numérico deve conter exatamente 4 dígitos.',
            'codigo.unique' => 'Já existe uma disciplina com esse código nesse período.',
            'nome.required' => 'O nome da disciplina é obrigatório.',
            'professor_email.email' => 'O e-mail do professor deve ser válido.',
        ];
    }
}

