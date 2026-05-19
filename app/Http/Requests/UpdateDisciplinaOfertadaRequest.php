<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;

class UpdateDisciplinaOfertadaRequest extends FormRequest
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
        $disciplina = $this->route('disciplina_ofertada');
        $disciplinaId = $disciplina instanceof Model ? $disciplina->getKey() : null;
        $periodoId = $this->integer('periodo_id');
        $departamento = $this->string('departamento')->toString();

        return [
            'periodo_id' => 'required|integer|exists:periodos,id',
            'departamento' => 'required|string|in:MAT,MAC,MAP,MAE',
            'nome' => 'required|string|max:255',
            'professor_nome' => 'required|string|max:255',
            'professor_email' => 'required|email:rfc,dns|max:255',
            // evita duplicidade dentro do mesmo período
            'codigo' => [
                'required',
                'string',
                'regex:/^\d{4}$/',
                Rule::unique('disciplinas_ofertadas', 'codigo')
                    ->where(fn (Builder $q) => $q
                        ->where('periodo_id', $periodoId)
                        ->where('departamento', $departamento))
                    ->ignore($disciplinaId),
            ],
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
            'departamento.in' => 'O departamento deve ser MAT, MAC, MAP ou MAE.',
            'codigo.required' => 'O código numérico é obrigatório.',
            'codigo.regex' => 'O código numérico deve conter exatamente 4 dígitos.',
            'codigo.unique' => 'Já existe uma disciplina com esse código nesse período.',
            'nome.required' => 'O nome da disciplina é obrigatório.',
            'professor_nome.required' => 'O nome do professor é obrigatório.',
            'professor_email.required' => 'O e-mail do professor é obrigatório.',
            'professor_email.email' => 'O e-mail do professor deve ser válido.',
        ];
    }
}

