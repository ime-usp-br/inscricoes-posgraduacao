<?php

namespace App\Services;

use App\Contracts\WspixClient;
use App\Enums\PagamentoDisciplinaStatus;
use App\Models\Inscricao;
use Illuminate\Support\Carbon;
use RuntimeException;

class InscricaoPagamentoService
{
    public function __construct(
        private readonly WspixClient $wspix,
    ) {}

    public function valorPorDisciplina(): float
    {
        return (float) config('wspix.valor_por_disciplina', 107);
    }

    public function quantidadeDisciplinasComPagamento(Inscricao $inscricao): int
    {
        return count($inscricao->disciplinasComPagamentoPendente());
    }

    public function valorTotal(Inscricao $inscricao): float
    {
        return round(
            $this->valorPorDisciplina() * $this->quantidadeDisciplinasComPagamento($inscricao),
            2
        );
    }

    /**
     * Garante Pix ativo com valor e quantidade de disciplinas corretos.
     *
     * @return array<string, mixed> dados do Pix (consulta ou geração)
     */
    public function garantirPixAtivo(Inscricao $inscricao): array
    {
        if (! $inscricao->temPagamentoPendente()) {
            throw new RuntimeException('Esta inscrição não possui pagamento pendente.');
        }

        $quantidade = $this->quantidadeDisciplinasComPagamento($inscricao);
        $valor = $this->valorTotal($inscricao);

        if ($quantidade < 1 || $valor <= 0) {
            throw new RuntimeException('Nenhuma disciplina exige pagamento.');
        }

        if (
            $inscricao->pix_idfpix !== null
            && ! $inscricao->precisaGerarNovoPix($quantidade, $valor)
        ) {
            $dados = $this->wspix->consultar($inscricao->pix_idfpix);

            if ($this->pixEstaConcluido($dados)) {
                $this->confirmarPagamento($inscricao, $dados);

                return $dados;
            }

            $this->atualizarPixDaConsulta($inscricao, $dados, $quantidade, $valor);

            return $dados;
        }

        return $this->gerarNovoPix($inscricao, $quantidade, $valor);
    }

    /**
     * @return array<string, mixed>
     */
    public function verificarPagamento(Inscricao $inscricao): array
    {
        if ($inscricao->pix_idfpix === null) {
            return $this->garantirPixAtivo($inscricao);
        }

        $dados = $this->wspix->consultar($inscricao->pix_idfpix, verificar: true);

        if ($this->pixEstaConcluido($dados)) {
            $this->confirmarPagamento($inscricao, $dados);
        } else {
            $quantidade = $this->quantidadeDisciplinasComPagamento($inscricao);
            $this->atualizarPixDaConsulta($inscricao, $dados, $quantidade, $this->valorTotal($inscricao));
        }

        return $dados;
    }

    /**
     * @param  array<string, mixed>  $dados
     */
    public function confirmarPagamento(Inscricao $inscricao, array $dados): void
    {
        if (! $inscricao->temPagamentoPendente()) {
            return;
        }

        $inscricao->marcarTodasDisciplinasPagas();

        $dataPag = $dados['dataPag'] ?? null;
        $pagoEm = is_string($dataPag) && $dataPag !== ''
            ? Carbon::parse($dataPag)
            : now();

        $inscricao->pix_status = 'Concluído';
        $inscricao->pix_pago_em = $pagoEm;
        $inscricao->save();
    }

    /**
     * @param  array<string, mixed>  $dados
     */
    private function gerarNovoPix(Inscricao $inscricao, int $quantidade, float $valor): array
    {
        $cpf = $inscricao->cpfCandidato();
        if ($cpf === null) {
            throw new RuntimeException('CPF do candidato não encontrado para gerar o Pix.');
        }

        $periodo = $inscricao->periodo;
        $periodoLabel = $periodo !== null
            ? $periodo->ano.'/'.$periodo->semestre
            : 'período atual';

        $disciplinas = collect($inscricao->disciplinasComPagamentoPendente())
            ->map(fn (array $item): string => $item['disciplina']->codigo_completo)
            ->implode(', ');

        $payload = [
            'tipoPessoa' => 'PF',
            'docPesOrg' => $cpf,
            'nomePesOrg' => $this->sanitizeForWspix($inscricao->nome_completo, 150),
            'valor' => $this->formatValorParaWspix($valor),
            'infoCobranca' => $this->sanitizeForWspix(sprintf(
                'Inscricao pos-graduacao %s - %d disciplina(s): %s',
                $periodoLabel,
                $quantidade,
                $disciplinas
            ), 140),
            'emailPesOrg' => $inscricao->email,
            'expiracao' => (int) config('wspix.expiracao_segundos', 86400),
            'codigoFonteRecurso' => (int) config('wspix.codigo_fonte_recurso'),
            'codigoUnidadeDespesa' => (int) config('wspix.codigo_unidade_despesa'),
            'estruturaHierarquica' => (string) config('wspix.estrutura_hierarquica'),
        ];

        $dados = $this->wspix->gerar($payload);

        $inscricao->pix_idfpix = isset($dados['idfpix']) ? (string) $dados['idfpix'] : null;
        $inscricao->pix_status = 'Ativo';
        $inscricao->pix_valor = $valor;
        $inscricao->pix_quantidade_disciplinas = $quantidade;
        $inscricao->pix_qr_code = isset($dados['qrCode']) ? (string) $dados['qrCode'] : null;
        $inscricao->pix_gerado_em = now();
        $inscricao->pix_pago_em = null;
        $inscricao->save();

        return $dados;
    }

    /**
     * @param  array<string, mixed>  $dados
     */
    private function atualizarPixDaConsulta(
        Inscricao $inscricao,
        array $dados,
        int $quantidade,
        float $valor,
    ): void {
        $status = isset($dados['status']) ? (string) $dados['status'] : $inscricao->pix_status;

        $inscricao->pix_status = $status;
        $inscricao->pix_valor = $valor;
        $inscricao->pix_quantidade_disciplinas = $quantidade;

        if (isset($dados['qrCode']) && is_string($dados['qrCode']) && $dados['qrCode'] !== '') {
            $inscricao->pix_qr_code = $dados['qrCode'];
        }

        $inscricao->save();
    }

    /**
     * @param  array<string, mixed>  $dados
     */
    public function pixEstaConcluido(array $dados): bool
    {
        $status = $dados['status'] ?? null;

        return in_array($status, ['Concluído', 'Pago'], true);
    }

    private function sanitizeForWspix(string $value, int $maxLength): string
    {
        $normalized = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if (! is_string($normalized) || $normalized === '') {
            $normalized = $value;
        }

        $normalized = preg_replace('/[^\x20-\x7E]/', '', $normalized) ?? '';
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? '';
        $normalized = trim($normalized);

        if ($normalized === '') {
            $normalized = 'Pagamento de inscricao';
        }

        return substr($normalized, 0, $maxLength);
    }

    private function formatValorParaWspix(float $valor): string
    {
        // WSPIX documenta "formato pt-BR" para valor decimal.
        return number_format($valor, 2, ',', '');
    }
}
