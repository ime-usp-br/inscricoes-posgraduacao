<?php

namespace App\Http\Requests;

use App\Models\Inscricao;
use Illuminate\Foundation\Http\FormRequest;

class InscricaoEtapa2Request extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function (\Illuminate\Validation\Validator $validator): void {
            $inscricao = $this->inscricaoDaSessao();
            if ($inscricao === null || $inscricao->etapa_concluida !== 1) {
                $validator->errors()->add('sessao', 'Sessão inválida ou etapa incorreta. Acesse a página inicial e reinicie se necessário.');
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $inscricao = $this->inscricaoDaSessao();
        if ($inscricao === null) {
            return [];
        }

        if ($inscricao->aluno_usp) {
            return [
                'unidade' => 'required|string|max:255',
                'pdf_comprovante_matricula' => 'required|file|mimes:pdf|max:12288',
                'pdf_historico_escolar' => 'required|file|mimes:pdf|max:12288',
            ];
        }

        $rules = [
            'pos_graduacao_externo' => 'required|in:sim,nao',
            'nome_programa_externo' => 'required_if:pos_graduacao_externo,sim|nullable|string|max:500',
            'curso_usp_anterior' => 'required|in:sim,nao',
            'data_nascimento' => 'required|date',
            'genero' => 'required|string|max:64',
            'nome_mae' => 'required|string|max:255',
            'cpf' => 'required|string|max:20',
            'rg_rne_rnm' => 'required|string|max:32',
            'visto_estudante_mercosul' => 'nullable|string|max:255',
            'orgao_expedidor' => 'required|string|max:128',
            'estado_expedicao' => 'required|string|max:64',
            'data_expedicao' => 'required|date',
            'pais_nascimento' => 'required|string|max:128',
            'estado_nascimento' => 'required|string|max:128',
            'municipio_provincia' => 'required|string|max:128',
            'nacionalidade' => 'required|string|max:128',
            'endereco_completo' => 'required|string|max:500',
            'cep' => 'required|string|max:20',
            'telefone' => 'required|string|max:40',
            'estrangeiro' => 'required|in:sim,nao',
            'pdf_diploma_graduacao' => 'required|file|mimes:pdf|max:12288',
            'pdf_historico_graduacao' => 'required|file|mimes:pdf|max:12288',
            'pdf_rg_rne_rnm' => 'required|file|mimes:pdf|max:12288',
            'pdf_cpf' => 'required|file|mimes:pdf|max:12288',
            'pdf_passaporte' => 'required_if:estrangeiro,sim|nullable|file|mimes:pdf|max:12288',
            'pdf_visto_estudante_mercosul' => 'required_if:estrangeiro,sim|nullable|file|mimes:pdf|max:12288',
        ];

        return $rules;
    }

    private function inscricaoDaSessao(): ?Inscricao
    {
        $raw = $this->session()->get('inscricao_id');
        if (is_int($raw)) {
            $id = $raw;
        } elseif (is_string($raw) && ctype_digit($raw)) {
            $id = (int) $raw;
        } else {
            return null;
        }

        return Inscricao::query()->whereKey($id)->first();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'unidade.required' => 'Informe a unidade USP.',
            'pdf_comprovante_matricula.required' => 'Envie o PDF do comprovante de matrícula.',
            'pdf_historico_escolar.required' => 'Envie o PDF do histórico escolar.',
            'nome_programa_externo.required_if' => 'Especifique o nome do programa de pós-graduação externo.',
            'pdf_passaporte.required_if' => 'Envie o PDF do passaporte.',
            'pdf_visto_estudante_mercosul.required_if' => 'Envie o PDF do visto estudante ou Mercosul.',
        ];
    }
}
