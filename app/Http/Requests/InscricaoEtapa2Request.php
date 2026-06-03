<?php

namespace App\Http\Requests;

use App\Models\Inscricao;
use Illuminate\Foundation\Http\FormRequest;

class InscricaoEtapa2Request extends FormRequest
{
    /** Tamanho máximo por PDF (regra max do Laravel em kilobytes). */
    private const PDF_MAX_KILOBYTES = 1024;

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
                'pdf_comprovante_matricula' => $this->regraPdfObrigatoria(),
                'pdf_historico_escolar' => $this->regraPdfObrigatoria(),
            ];
        }

        $rules = [
            'pos_graduacao_externo' => 'required|in:sim,nao',
            'nome_programa_externo' => 'required_if:pos_graduacao_externo,sim|nullable|string|max:500',
            'formado_graduacao_usp' => 'required|in:sim,nao',
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
            'pdf_diploma_graduacao' => $this->regraPdfObrigatoria(),
            'pdf_historico_graduacao' => $this->regraPdfObrigatoria(),
            'pdf_rg_rne_rnm' => $this->regraPdfObrigatoria(),
            'pdf_cpf' => $this->regraPdfObrigatoria(),
            'pdf_passaporte' => $this->regraPdfCondicionalEstrangeiro(),
            'pdf_visto_estudante_mercosul' => $this->regraPdfCondicionalEstrangeiro(),
        ];

        return $rules;
    }

    private function regraPdfObrigatoria(): string
    {
        return 'required|file|mimes:pdf|max:'.self::PDF_MAX_KILOBYTES;
    }

    private function regraPdfCondicionalEstrangeiro(): string
    {
        return 'required_if:estrangeiro,sim|nullable|file|mimes:pdf|max:'.self::PDF_MAX_KILOBYTES;
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
        $pdfMax = 'Cada arquivo PDF deve ter no máximo 1 MB.';
        $pdfMimes = 'O arquivo deve estar no formato PDF.';

        return [
            'unidade.required' => 'Informe a unidade USP.',
            'pdf_comprovante_matricula.required' => 'Envie o PDF do comprovante de matrícula.',
            'pdf_comprovante_matricula.max' => $pdfMax,
            'pdf_comprovante_matricula.mimes' => $pdfMimes,
            'pdf_historico_escolar.required' => 'Envie o PDF do histórico escolar.',
            'pdf_historico_escolar.max' => $pdfMax,
            'pdf_historico_escolar.mimes' => $pdfMimes,
            'pdf_diploma_graduacao.required' => 'Envie o PDF do diploma de graduação.',
            'pdf_diploma_graduacao.max' => $pdfMax,
            'pdf_diploma_graduacao.mimes' => $pdfMimes,
            'pdf_historico_graduacao.required' => 'Envie o PDF do histórico da graduação.',
            'pdf_historico_graduacao.max' => $pdfMax,
            'pdf_historico_graduacao.mimes' => $pdfMimes,
            'pdf_rg_rne_rnm.required' => 'Envie o PDF do RG / RNE / RNM.',
            'pdf_rg_rne_rnm.max' => $pdfMax,
            'pdf_rg_rne_rnm.mimes' => $pdfMimes,
            'pdf_cpf.required' => 'Envie o PDF do CPF.',
            'pdf_cpf.max' => $pdfMax,
            'pdf_cpf.mimes' => $pdfMimes,
            'nome_programa_externo.required_if' => 'Especifique o nome do programa de pós-graduação externo.',
            'formado_graduacao_usp.required' => 'Informe se é formado na graduação USP.',
            'formado_graduacao_usp.in' => 'A resposta deve ser Sim ou Não.',
            'pdf_passaporte.required_if' => 'Envie o PDF do passaporte.',
            'pdf_passaporte.max' => $pdfMax,
            'pdf_passaporte.mimes' => $pdfMimes,
            'pdf_visto_estudante_mercosul.required_if' => 'Envie o PDF do visto estudante ou Mercosul.',
            'pdf_visto_estudante_mercosul.max' => $pdfMax,
            'pdf_visto_estudante_mercosul.mimes' => $pdfMimes,
        ];
    }
}
