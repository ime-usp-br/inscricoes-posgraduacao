<?php

namespace App\Services;

use App\Exceptions\ReplicadoServiceException;
use App\Models\DisciplinaOfertada;
use App\Models\Periodo;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;
use Uspdev\Replicado\DB as ReplicadoDB;

class ReplicadoDisciplinaOfertadaService
{
    /** @var list<string> */
    public const DEPARTAMENTOS_REPLICADO = ['MAT', 'MAC', 'MAP', 'MAE', 'MPM'];

    /**
     * @return list<array{sgldis: string, departamento: string, codigo: string, nome: string}>
     */
    public function listarParaPeriodo(string $dataInicioInscricao): array
    {
        $linhas = $this->buscarNoReplicado($dataInicioInscricao);
        $disciplinas = [];

        foreach ($linhas as $linha) {
            $parsed = $this->parseLinhaReplicado($linha);
            if ($parsed === null) {
                continue;
            }

            $disciplinas[$parsed['sgldis']] = $parsed;
        }

        $resultado = array_values($disciplinas);
        usort($resultado, fn (array $a, array $b): int => strcmp($a['sgldis'], $b['sgldis']));

        return $resultado;
    }

    public function importarParaPeriodo(Periodo $periodo, string $dataInicioInscricao): int
    {
        $disciplinas = $this->listarParaPeriodo($dataInicioInscricao);
        $importadas = 0;

        foreach ($disciplinas as $disciplina) {
            DisciplinaOfertada::query()->updateOrCreate(
                [
                    'periodo_id' => $periodo->id,
                    'departamento' => $disciplina['departamento'],
                    'codigo' => $disciplina['codigo'],
                ],
                [
                    // Não sobrescrever professor_nome/professor_email: são preenchidos pela
                    // secretaria e definem quem pode avaliar a disciplina.
                    'nome' => $disciplina['nome'],
                ],
            );

            $importadas++;
        }

        return $importadas;
    }

    /**
     * Interpreta sgldis no formato "MAT1111" (3 letras + dígitos).
     *
     * @return array{sgldis: string, departamento: string, codigo: string}|null
     */
    public function parseSgldis(string $sgldis): ?array
    {
        $sgldis = strtoupper(trim($sgldis));

        if (! preg_match('/^([A-Z]{3})(\d{1,4})$/', $sgldis, $matches)) {
            return null;
        }

        $departamento = $matches[1];
        if (! in_array($departamento, self::DEPARTAMENTOS_REPLICADO, true)) {
            return null;
        }

        return [
            'sgldis' => $sgldis,
            'departamento' => $departamento,
            'codigo' => str_pad($matches[2], 4, '0', STR_PAD_LEFT),
        ];
    }

    /**
     * Formata a data para o literal exigido pelo Replicado/Sybase: 'YYYY-MM-DD'.
     */
    public function formatarDataParaReplicado(string $dataInicioInscricao): string
    {
        $data = Carbon::parse($dataInicioInscricao)->format('Y-m-d');

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
            throw new InvalidArgumentException('Data inválida para consulta ao Replicado.');
        }

        return $data;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buscarNoReplicado(string $dataInicioInscricao): array
    {
        $dataSql = $this->formatarDataParaReplicado($dataInicioInscricao);

        // Literal entre aspas: o uspdev/replicado faz bind por :chave e não substitui "?" corretamente.
        $sql = "
            SELECT DISTINCT o.sgldis, d.nomdis
            FROM OFERECIMENTO o
            INNER JOIN DISCIPLINA d ON d.sgldis = o.sgldis
            WHERE o.dtainiofe > '{$dataSql}'
              AND (
                o.sgldis LIKE 'MAC%'
                OR o.sgldis LIKE 'MAT%'
                OR o.sgldis LIKE 'MAE%'
                OR o.sgldis LIKE 'MAP%'
                OR o.sgldis LIKE 'MPM%'
              )
            ORDER BY o.sgldis
        ";

        try {
            $resultado = ReplicadoDB::fetchAll($sql);
        } catch (Throwable $exception) {
            Log::error('Erro ao consultar disciplinas no Replicado.', [
                'data_inicio' => $dataInicioInscricao,
                'data_sql' => $dataSql,
                'exception' => $exception,
            ]);

            throw new ReplicadoServiceException(
                'Não foi possível consultar as disciplinas no Replicado.',
                0,
                $exception
            );
        }

        if (! is_array($resultado)) {
            return [];
        }

        return $resultado;
    }

    /**
     * @param  array<string, mixed>  $linha
     * @return array{sgldis: string, departamento: string, codigo: string, nome: string}|null
     */
    private function parseLinhaReplicado(array $linha): ?array
    {
        $sgldis = isset($linha['sgldis']) ? (string) $linha['sgldis'] : '';
        $parsed = $this->parseSgldis($sgldis);

        if ($parsed === null) {
            Log::warning('Sigla de disciplina ignorada no Replicado.', ['sgldis' => $sgldis]);

            return null;
        }

        $nome = isset($linha['nomdis']) ? trim((string) $linha['nomdis']) : '';
        if ($nome === '') {
            $nome = $parsed['sgldis'];
        }

        return [
            'sgldis' => $parsed['sgldis'],
            'departamento' => $parsed['departamento'],
            'codigo' => $parsed['codigo'],
            'nome' => $nome,
        ];
    }
}
