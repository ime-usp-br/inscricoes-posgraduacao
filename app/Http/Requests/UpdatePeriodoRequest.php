<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;

class UpdatePeriodoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $periodo = $this->route('periodo');
        $periodoId = $periodo instanceof Model ? $periodo->getKey() : null;
        $semestre = $this->integer('semestre');

        return [
            "ano" => [
                'required',
                'integer',
                Rule::unique('periodos', 'ano')
                    ->where(fn (Builder $q) => $q->where('semestre', $semestre))
                    ->ignore($periodoId),
            ],
            "semestre" => 'required|integer|in:1,2',
            "data_inicio_inscricao" => 'required|date|before:data_fim_inscricao',
            "data_fim_inscricao" => 'required|date|after:data_inicio_inscricao',
            "status" => 'required|in:aberto,fechado',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ano.required' => 'O campo ano é obrigatório.',
            'ano.integer' => 'O campo ano deve ser um número inteiro.',
            'ano.unique' => 'Já existe um período cadastrado para esse ano/semestre.',
            'semestre.required' => 'O campo semestre é obrigatório.',
            'semestre.integer' => 'O campo semestre deve ser um número inteiro.',
            'semestre.in' => 'O campo semestre deve ser 1 ou 2.',
            'data_inicio_inscricao.required' => 'O campo data de início das inscrições é obrigatório.',
            'data_inicio_inscricao.date' => 'O campo data de início das inscrições deve ser uma data válida.',
            'data_inicio_inscricao.before' => 'A data de início das inscrições deve ser anterior à data de fim das inscrições.',
            'data_fim_inscricao.required' => 'O campo data de fim das inscrições é obrigatório.',
            'data_fim_inscricao.date' => 'O campo data de fim das inscrições deve ser uma data válida.',
            'data_fim_inscricao.after' => 'A data de fim das inscrições deve ser posterior à data de início das inscrições.',
            'status.required' => 'O campo status é obrigatório.',
            'status.in' => 'O status deve ser aberto ou fechado.',
        ];
    }
}
