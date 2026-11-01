<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DecidirSolicitacaoIsencaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'decisao' => ['required', Rule::in(['aprovar', 'reprovar'])],
            'resposta_secretaria' => ['nullable', 'string', 'max:2000'],
            'motivo_reprovacao' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function (\Illuminate\Validation\Validator $validator): void {
            if ($this->input('decisao') === 'reprovar' && blank($this->input('motivo_reprovacao'))) {
                $validator->errors()->add('motivo_reprovacao', 'Informe o motivo da reprovação.');
            }
        });
    }
}
