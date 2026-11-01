<?php

namespace App\Http\Requests;

use App\Enums\SolicitacaoIsencaoStatus;
use App\Models\Inscricao;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SolicitacaoIsencaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'justificativa' => $this->string('justificativa')->trim()->toString(),
            'disciplinas' => array_values(array_filter(
                (array) $this->input('disciplinas', []),
                static fn (mixed $slot): bool => is_string($slot) && $slot !== ''
            )),
            'remover_documentos' => array_values(array_filter(
                (array) $this->input('remover_documentos', []),
                static fn (mixed $id): bool => is_numeric($id)
            )),
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $inscricao = $this->inscricaoDaSessao();

            if ($inscricao === null) {
                $validator->errors()->add('sessao', 'Sessão inválida ou inscrição incompleta.');

                return;
            }

            if (! $this->podeEditar($inscricao)) {
                $validator->errors()->add('sessao', 'Esta solicitação de isenção não pode mais ser alterada.');

                return;
            }

            $this->validarDisciplinas($validator, $inscricao);
            $this->validarQuantidadeDeDocumentos($validator, $inscricao);
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $maxArquivos = $this->configInt('isencao.documentos.max_arquivos', 5);
        $maxKb = $this->configInt('isencao.documentos.max_kb', 1024);
        $min = $this->configInt('isencao.justificativa.min', 20);
        $max = $this->configInt('isencao.justificativa.max', 2000);
        $solicitacao = $this->inscricaoDaSessao()?->solicitacaoIsencao;

        return [
            'justificativa' => ['required', 'string', "min:{$min}", "max:{$max}"],
            'disciplinas' => ['required', 'array', 'min:1'],
            'disciplinas.*' => ['string', Rule::in(Inscricao::SLOTS_DISCIPLINA)],
            'documentos' => ['nullable', 'array', "max:{$maxArquivos}"],
            'documentos.*' => ['file', 'mimes:pdf', "max:{$maxKb}"],
            'remover_documentos' => ['nullable', 'array'],
            'remover_documentos.*' => [
                'integer',
                Rule::exists('solicitacao_isencao_documentos', 'id')
                    ->where('solicitacao_isencao_id', $solicitacao !== null ? $solicitacao->id : 0),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $maxArquivos = $this->configInt('isencao.documentos.max_arquivos', 5);
        $min = $this->configInt('isencao.justificativa.min', 20);

        return [
            'justificativa.required' => 'Informe a justificativa do pedido de isenção.',
            'justificativa.min' => "A justificativa deve ter ao menos {$min} caracteres.",
            'disciplinas.required' => 'Selecione ao menos uma disciplina para solicitar isenção.',
            'documentos.*.mimes' => 'Envie apenas arquivos PDF.',
            'documentos.*.max' => 'Cada documento deve ter no máximo 1 MB.',
            'documentos.max' => "Envie no máximo {$maxArquivos} documentos.",
        ];
    }

    /**
     * @return list<string>
     */
    public function slotsSelecionados(): array
    {
        $resultado = [];

        foreach ((array) $this->validated('disciplinas', []) as $slot) {
            if (is_string($slot)) {
                $resultado[$slot] = $slot;
            }
        }

        return array_values($resultado);
    }

    /**
     * @return list<UploadedFile>
     */
    public function documentosEnviados(): array
    {
        $arquivos = $this->file('documentos', []);

        if ($arquivos instanceof UploadedFile) {
            return [$arquivos];
        }

        /** @var list<UploadedFile> $lista */
        $lista = array_values((array) $arquivos);

        return $lista;
    }

    /**
     * @return list<int>
     */
    public function documentosParaRemover(): array
    {
        $resultado = [];

        foreach ((array) $this->input('remover_documentos', []) as $id) {
            if (is_numeric($id)) {
                $resultado[] = (int) $id;
            }
        }

        return $resultado;
    }

    private function validarDisciplinas(Validator $validator, Inscricao $inscricao): void
    {
        $slots = (array) $this->input('disciplinas', []);
        $solicitacao = $inscricao->solicitacaoIsencao;

        $elegiveis = array_column($inscricao->disciplinasElegiveisParaIsencao(), 'slot');

        if ($solicitacao?->status === SolicitacaoIsencaoStatus::Pendente) {
            $elegiveis = array_values(array_unique(array_merge(
                $elegiveis,
                array_column($solicitacao->disciplinas_snapshot ?? [], 'slot')
            )));
        }

        $invalidos = array_diff($slots, $elegiveis);

        if ($invalidos !== []) {
            $validator->errors()->add(
                'disciplinas',
                'Selecione apenas disciplinas com taxa de inscrição (MAC/MAE) disponíveis para isenção.'
            );
        }
    }

    private function validarQuantidadeDeDocumentos(Validator $validator, Inscricao $inscricao): void
    {
        $solicitacao = $inscricao->solicitacaoIsencao;
        $existentes = $solicitacao?->documentos()->count() ?? 0;
        $remover = count($this->documentosParaRemover());
        $enviados = count($this->documentosEnviados());
        $total = max(0, $existentes - $remover) + $enviados;

        if ($total < 1) {
            $validator->errors()->add('documentos', 'Anexe ao menos um documento em PDF.');
        }

        $maxArquivos = $this->configInt('isencao.documentos.max_arquivos', 5);

        if ($total > $maxArquivos) {
            $validator->errors()->add('documentos', "Envie no máximo {$maxArquivos} documentos.");
        }
    }

    private function podeEditar(Inscricao $inscricao): bool
    {
        if ($inscricao->etapa_concluida < 3) {
            return false;
        }

        $solicitacao = $inscricao->solicitacaoIsencao;

        return $solicitacao === null
            || in_array(
                $solicitacao->status,
                [SolicitacaoIsencaoStatus::Pendente, SolicitacaoIsencaoStatus::Cancelada],
                true
            );
    }

    private function inscricaoDaSessao(): ?Inscricao
    {
        $raw = $this->session()->get('inscricao_id');

        $id = match (true) {
            is_int($raw) => $raw,
            is_string($raw) && ctype_digit($raw) => (int) $raw,
            default => null,
        };

        if ($id === null) {
            return null;
        }

        return Inscricao::query()
            ->with([
                'periodo',
                'disciplinaObrigatoria',
                'disciplinaOpcional1',
                'disciplinaOpcional2',
                'solicitacaoIsencao.documentos',
            ])
            ->whereKey($id)
            ->first();
    }

    private function configInt(string $chave, int $padrao): int
    {
        $valor = config($chave, $padrao);

        return is_numeric($valor) ? (int) $valor : $padrao;
    }
}
