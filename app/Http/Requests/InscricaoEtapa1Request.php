<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InscricaoEtapa1Request extends FormRequest
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
            'nome_completo' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'aluno_usp' => 'required|in:sim,nao',
            'numero_usp' => 'required_if:aluno_usp,sim|nullable|string|max:32',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nome_completo.required' => 'Informe o nome completo.',
            'email.required' => 'Informe o e-mail.',
            'email.email' => 'Informe um e-mail válido.',
            'aluno_usp.required' => 'Indique se você é aluno USP.',
            'numero_usp.required_if' => 'Informe o número USP.',
        ];
    }
}
