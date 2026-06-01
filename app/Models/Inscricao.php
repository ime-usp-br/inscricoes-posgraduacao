<?php

namespace App\Models;

use App\Enums\AprovacaoProfessorDisciplina;
use App\Enums\AprovacaoSecretariaDisciplina;
use App\Enums\InscricaoStatus;
use App\Enums\PagamentoDisciplinaStatus;
use App\Enums\ResumoAprovacaoInscricao;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Inscricao extends Model
{
    /** @var list<string> */
    public const DEPARTAMENTOS_COM_PAGAMENTO = ['MAC', 'MAE'];

    /** @var list<string> */
    public const SLOTS_DISCIPLINA = ['obrigatoria', 'opcional_1', 'opcional_2'];

    /** @use HasFactory<\Database\Factories\InscricaoFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::deleting(function (Inscricao $inscricao): void {
            Storage::disk('local')->deleteDirectory('inscricoes/'.$inscricao->id);
        });
    }

    protected $fillable = [
        'periodo_id',
        'nome_completo',
        'email',
        'aluno_usp',
        'numero_usp',
        'dados_etapa_2',
        'etapa_concluida',
        'concluido_em',
        'disciplina_obrigatoria_id',
        'justificativa_disciplina_obrigatoria',
        'disciplina_opcional_1_id',
        'justificativa_disciplina_opcional_1',
        'disciplina_opcional_2_id',
        'justificativa_disciplina_opcional_2',
        'status',
        'aprovacao_obrigatoria_secretaria',
        'aprovacao_opcional_1_secretaria',
        'aprovacao_opcional_2_secretaria',
        'aprovacao_obrigatoria_professor',
        'aprovacao_opcional_1_professor',
        'aprovacao_opcional_2_professor',
        'pagamentos_disciplinas',
        'pix_idfpix',
        'pix_status',
        'pix_valor',
        'pix_quantidade_disciplinas',
        'pix_qr_code',
        'pix_gerado_em',
        'pix_pago_em',
    ];

    protected $casts = [
        'aluno_usp' => 'boolean',
        'dados_etapa_2' => 'array',
        'pagamentos_disciplinas' => 'array',
        'pix_valor' => 'decimal:2',
        'pix_gerado_em' => 'datetime',
        'pix_pago_em' => 'datetime',
        'concluido_em' => 'datetime',
        'status' => InscricaoStatus::class,
        'aprovacao_obrigatoria_secretaria' => AprovacaoSecretariaDisciplina::class,
        'aprovacao_opcional_1_secretaria' => AprovacaoSecretariaDisciplina::class,
        'aprovacao_opcional_2_secretaria' => AprovacaoSecretariaDisciplina::class,
        'aprovacao_obrigatoria_professor' => AprovacaoProfessorDisciplina::class,
        'aprovacao_opcional_1_professor' => AprovacaoProfessorDisciplina::class,
        'aprovacao_opcional_2_professor' => AprovacaoProfessorDisciplina::class,
    ];

    /**
     * @param  Builder<Inscricao>  $query
     * @return Builder<Inscricao>
     */
    public function scopeComDisciplinaAprovadaPelaSecretaria(Builder $query): Builder
    {
        return $query->where(function (Builder $q): void {
            $q->where('aprovacao_obrigatoria_secretaria', AprovacaoSecretariaDisciplina::Aprovado)
                ->orWhere('aprovacao_opcional_1_secretaria', AprovacaoSecretariaDisciplina::Aprovado)
                ->orWhere('aprovacao_opcional_2_secretaria', AprovacaoSecretariaDisciplina::Aprovado);
        });
    }

    /**
     * @return list<array{slot: string, disciplina: DisciplinaOfertada, aprovacao: string, justificativa: ?string}>
     */
    public function disciplinasParaAprovacaoSecretaria(): array
    {
        $slots = [
            'obrigatoria' => [
                'disciplina' => $this->disciplinaObrigatoria,
                'aprovacao' => 'aprovacao_obrigatoria_secretaria',
                'justificativa' => $this->justificativa_disciplina_obrigatoria,
            ],
            'opcional_1' => [
                'disciplina' => $this->disciplinaOpcional1,
                'aprovacao' => 'aprovacao_opcional_1_secretaria',
                'justificativa' => $this->justificativa_disciplina_opcional_1,
            ],
            'opcional_2' => [
                'disciplina' => $this->disciplinaOpcional2,
                'aprovacao' => 'aprovacao_opcional_2_secretaria',
                'justificativa' => $this->justificativa_disciplina_opcional_2,
            ],
        ];

        $result = [];
        foreach ($slots as $slot => $config) {
            if ($config['disciplina'] === null) {
                continue;
            }

            $result[] = [
                'slot' => $slot,
                'disciplina' => $config['disciplina'],
                'aprovacao' => $config['aprovacao'],
                'justificativa' => $config['justificativa'],
            ];
        }

        return $result;
    }

    public function aprovacaoSecretariaParaSlot(string $slot): ?AprovacaoSecretariaDisciplina
    {
        return match ($slot) {
            'obrigatoria' => $this->aprovacao_obrigatoria_secretaria,
            'opcional_1' => $this->aprovacao_opcional_1_secretaria,
            'opcional_2' => $this->aprovacao_opcional_2_secretaria,
            default => null,
        };
    }

    public function marcarDisciplinaAprovadaPelaSecretaria(string $slot): void
    {
        $this->marcarDisciplinaSecretaria($slot, AprovacaoSecretariaDisciplina::Aprovado);
    }

    public function marcarDisciplinaReprovadaPelaSecretaria(string $slot): void
    {
        $this->marcarDisciplinaSecretaria($slot, AprovacaoSecretariaDisciplina::Reprovado);
    }

    private function marcarDisciplinaSecretaria(string $slot, AprovacaoSecretariaDisciplina $aprovacao): void
    {
        $field = match ($slot) {
            'obrigatoria' => 'aprovacao_obrigatoria_secretaria',
            'opcional_1' => 'aprovacao_opcional_1_secretaria',
            'opcional_2' => 'aprovacao_opcional_2_secretaria',
            default => null,
        };

        if ($field === null) {
            return;
        }

        $this->{$field} = $aprovacao;
        $this->sincronizarStatusSecretaria();
        $this->save();
    }

    public function resumoAprovacaoSecretaria(): ResumoAprovacaoInscricao
    {
        return $this->calcularResumoEtapa(
            $this->disciplinasParaAprovacaoSecretaria(),
            'aprovacao_obrigatoria_secretaria',
            'aprovacao_opcional_1_secretaria',
            'aprovacao_opcional_2_secretaria',
            AprovacaoSecretariaDisciplina::Aprovado,
            AprovacaoSecretariaDisciplina::Reprovado,
        );
    }

    public function resumoAprovacaoProfessor(): ResumoAprovacaoInscricao
    {
        $disciplinas = $this->disciplinasParaAprovacaoProfessor();

        if ($disciplinas === []) {
            return ResumoAprovacaoInscricao::NaoAplicavel;
        }

        return $this->calcularResumoEtapa(
            $disciplinas,
            'aprovacao_obrigatoria_professor',
            'aprovacao_opcional_1_professor',
            'aprovacao_opcional_2_professor',
            AprovacaoProfessorDisciplina::Aprovado,
            AprovacaoProfessorDisciplina::Reprovado,
        );
    }

    /**
     * @param  list<array{slot: string, disciplina: DisciplinaOfertada, aprovacao: string, justificativa: ?string}>  $disciplinas
     */
    private function calcularResumoEtapa(
        array $disciplinas,
        string $campoObrigatoria,
        string $campoOpcional1,
        string $campoOpcional2,
        AprovacaoSecretariaDisciplina|AprovacaoProfessorDisciplina $aprovado,
        AprovacaoSecretariaDisciplina|AprovacaoProfessorDisciplina $reprovado,
    ): ResumoAprovacaoInscricao {
        if ($disciplinas === []) {
            return ResumoAprovacaoInscricao::Pendente;
        }

        $camposPorSlot = [
            'obrigatoria' => $campoObrigatoria,
            'opcional_1' => $campoOpcional1,
            'opcional_2' => $campoOpcional2,
        ];

        $temAprovacao = collect($disciplinas)->contains(
            fn (array $item): bool => $this->{$camposPorSlot[$item['slot']]} === $aprovado
        );

        if ($temAprovacao) {
            return ResumoAprovacaoInscricao::Aprovada;
        }

        $todasAvaliadas = collect($disciplinas)->every(
            fn (array $item): bool => $this->{$camposPorSlot[$item['slot']]} !== null
        );

        $todasReprovadas = collect($disciplinas)->every(
            fn (array $item): bool => $this->{$camposPorSlot[$item['slot']]} === $reprovado
        );

        if ($todasAvaliadas && $todasReprovadas) {
            return ResumoAprovacaoInscricao::Reprovada;
        }

        return ResumoAprovacaoInscricao::Pendente;
    }

    public function sincronizarStatusSecretaria(): void
    {
        $disciplinas = $this->disciplinasParaAprovacaoSecretaria();

        if ($disciplinas === []) {
            $this->status = InscricaoStatus::Inscrito;

            return;
        }

        $this->status = match ($this->resumoAprovacaoSecretaria()) {
            ResumoAprovacaoInscricao::Aprovada => InscricaoStatus::AprovadoSecretaria,
            ResumoAprovacaoInscricao::Reprovada => InscricaoStatus::ReprovadoSecretaria,
            default => InscricaoStatus::Inscrito,
        };

        $this->sincronizarStatusProfessor();
    }

    /**
     * @return list<array{slot: string, disciplina: DisciplinaOfertada, aprovacao: string, justificativa: ?string}>
     */
    public function disciplinasParaAprovacaoProfessor(): array
    {
        return array_values(array_filter(
            $this->disciplinasParaAprovacaoSecretaria(),
            fn (array $item): bool => $this->{$item['aprovacao']} === AprovacaoSecretariaDisciplina::Aprovado
        ));
    }

    public function aprovacaoProfessorParaSlot(string $slot): ?AprovacaoProfessorDisciplina
    {
        return match ($slot) {
            'obrigatoria' => $this->aprovacao_obrigatoria_professor,
            'opcional_1' => $this->aprovacao_opcional_1_professor,
            'opcional_2' => $this->aprovacao_opcional_2_professor,
            default => null,
        };
    }

    public function marcarDisciplinaAprovadaPeloProfessor(string $slot): void
    {
        $this->marcarDisciplinaProfessor($slot, AprovacaoProfessorDisciplina::Aprovado);
    }

    public function marcarDisciplinaReprovadaPeloProfessor(string $slot): void
    {
        $this->marcarDisciplinaProfessor($slot, AprovacaoProfessorDisciplina::Reprovado);
    }

    private function marcarDisciplinaProfessor(string $slot, AprovacaoProfessorDisciplina $aprovacao): void
    {
        if ($this->aprovacaoSecretariaParaSlot($slot) !== AprovacaoSecretariaDisciplina::Aprovado) {
            return;
        }

        $field = match ($slot) {
            'obrigatoria' => 'aprovacao_obrigatoria_professor',
            'opcional_1' => 'aprovacao_opcional_1_professor',
            'opcional_2' => 'aprovacao_opcional_2_professor',
            default => null,
        };

        if ($field === null) {
            return;
        }

        $this->{$field} = $aprovacao;
        $this->sincronizarStatusProfessor();
        $this->save();
    }

    public function sincronizarStatusProfessor(): void
    {
        $resumoProfessor = $this->resumoAprovacaoProfessor();

        if ($resumoProfessor === ResumoAprovacaoInscricao::NaoAplicavel) {
            return;
        }

        if ($resumoProfessor === ResumoAprovacaoInscricao::Aprovada) {
            $this->status = InscricaoStatus::AprovadoProfessor;

            return;
        }

        if ($resumoProfessor === ResumoAprovacaoInscricao::Reprovada) {
            $this->status = InscricaoStatus::ReprovadoProfessor;

            return;
        }

        $this->status = match ($this->resumoAprovacaoSecretaria()) {
            ResumoAprovacaoInscricao::Aprovada => InscricaoStatus::AprovadoSecretaria,
            ResumoAprovacaoInscricao::Reprovada => InscricaoStatus::ReprovadoSecretaria,
            default => InscricaoStatus::Inscrito,
        };
    }

    /**
     * @param  Builder<Inscricao>  $query
     * @return Builder<Inscricao>
     */
    public function scopeFiltrarResumoSecretaria(Builder $query, ResumoAprovacaoInscricao $resumo): Builder
    {
        return match ($resumo) {
            ResumoAprovacaoInscricao::Aprovada => $query->comDisciplinaAprovadaPelaSecretaria(),
            ResumoAprovacaoInscricao::Reprovada => $query
                ->whereNot(function (Builder $sub): void {
                    $sub->comDisciplinaAprovadaPelaSecretaria();
                })
                ->where(function (Builder $q): void {
                    $q->where(function (Builder $inner): void {
                        $inner->whereNull('disciplina_obrigatoria_id')
                            ->orWhere('aprovacao_obrigatoria_secretaria', AprovacaoSecretariaDisciplina::Reprovado);
                    })
                        ->where(function (Builder $inner): void {
                            $inner->whereNull('disciplina_opcional_1_id')
                                ->orWhere('aprovacao_opcional_1_secretaria', AprovacaoSecretariaDisciplina::Reprovado);
                        })
                        ->where(function (Builder $inner): void {
                            $inner->whereNull('disciplina_opcional_2_id')
                                ->orWhere('aprovacao_opcional_2_secretaria', AprovacaoSecretariaDisciplina::Reprovado);
                        })
                        ->where(function (Builder $inner): void {
                            $inner->whereNotNull('disciplina_obrigatoria_id')
                                ->orWhereNotNull('disciplina_opcional_1_id')
                                ->orWhereNotNull('disciplina_opcional_2_id');
                        });
                }),
            ResumoAprovacaoInscricao::Pendente => $query
                ->whereNot(function (Builder $sub): void {
                    $sub->comDisciplinaAprovadaPelaSecretaria();
                })
                ->whereNot(function (Builder $sub): void {
                    $sub->filtrarResumoSecretaria(ResumoAprovacaoInscricao::Reprovada);
                }),
            default => $query,
        };
    }

    /**
     * @param  Builder<Inscricao>  $query
     * @return Builder<Inscricao>
     */
    public function scopeFiltrarResumoProfessor(Builder $query, ResumoAprovacaoInscricao $resumo): Builder
    {
        return match ($resumo) {
            ResumoAprovacaoInscricao::Aprovada => $query->where(function (Builder $q): void {
                $q->where('aprovacao_obrigatoria_professor', AprovacaoProfessorDisciplina::Aprovado)
                    ->orWhere('aprovacao_opcional_1_professor', AprovacaoProfessorDisciplina::Aprovado)
                    ->orWhere('aprovacao_opcional_2_professor', AprovacaoProfessorDisciplina::Aprovado);
            }),
            ResumoAprovacaoInscricao::Reprovada => $query
                ->comDisciplinaAprovadaPelaSecretaria()
                ->whereNot(function (Builder $sub): void {
                    $sub->filtrarResumoProfessor(ResumoAprovacaoInscricao::Aprovada);
                })
                ->where(function (Builder $q): void {
                    $q->where(function (Builder $inner): void {
                        $inner->where('aprovacao_obrigatoria_secretaria', '!=', AprovacaoSecretariaDisciplina::Aprovado)
                            ->orWhere('aprovacao_obrigatoria_professor', AprovacaoProfessorDisciplina::Reprovado);
                    })
                        ->where(function (Builder $inner): void {
                            $inner->where('aprovacao_opcional_1_secretaria', '!=', AprovacaoSecretariaDisciplina::Aprovado)
                                ->orWhere('aprovacao_opcional_1_professor', AprovacaoProfessorDisciplina::Reprovado);
                        })
                        ->where(function (Builder $inner): void {
                            $inner->where('aprovacao_opcional_2_secretaria', '!=', AprovacaoSecretariaDisciplina::Aprovado)
                                ->orWhere('aprovacao_opcional_2_professor', AprovacaoProfessorDisciplina::Reprovado);
                        });
                }),
            ResumoAprovacaoInscricao::Pendente => $query
                ->comDisciplinaAprovadaPelaSecretaria()
                ->whereNot(function (Builder $sub): void {
                    $sub->filtrarResumoProfessor(ResumoAprovacaoInscricao::Aprovada);
                })
                ->whereNot(function (Builder $sub): void {
                    $sub->filtrarResumoProfessor(ResumoAprovacaoInscricao::Reprovada);
                }),
            default => $query,
        };
    }

    public function statusLabel(): string
    {
        return $this->status?->label() ?? InscricaoStatus::Inscrito->label();
    }

    public function justificativaParaSlot(string $slot): ?string
    {
        return match ($slot) {
            'obrigatoria' => $this->justificativa_disciplina_obrigatoria,
            'opcional_1' => $this->justificativa_disciplina_opcional_1,
            'opcional_2' => $this->justificativa_disciplina_opcional_2,
            default => null,
        };
    }

    /**
     * @phpstan-return BelongsTo<Periodo, $this>
     */
    public function periodo(): BelongsTo
    {
        return $this->belongsTo(Periodo::class);
    }

    /**
     * @phpstan-return BelongsTo<DisciplinaOfertada, $this>
     */
    public function disciplinaObrigatoria(): BelongsTo
    {
        return $this->belongsTo(DisciplinaOfertada::class, 'disciplina_obrigatoria_id');
    }

    /**
     * @phpstan-return BelongsTo<DisciplinaOfertada, $this>
     */
    public function disciplinaOpcional1(): BelongsTo
    {
        return $this->belongsTo(DisciplinaOfertada::class, 'disciplina_opcional_1_id');
    }

    /**
     * @phpstan-return BelongsTo<DisciplinaOfertada, $this>
     */
    public function disciplinaOpcional2(): BelongsTo
    {
        return $this->belongsTo(DisciplinaOfertada::class, 'disciplina_opcional_2_id');
    }

    public function isCandidatoSujeitoAPagamento(): bool
    {
        if ($this->aluno_usp) {
            return false;
        }

        return ($this->dados_etapa_2['formado_graduacao_usp'] ?? null) === 'nao';
    }

    public static function departamentoRequerPagamento(?DisciplinaOfertada $disciplina): bool
    {
        if ($disciplina === null) {
            return false;
        }

        return in_array(strtoupper($disciplina->departamento), self::DEPARTAMENTOS_COM_PAGAMENTO, true);
    }

    /**
     * @return array<string, DisciplinaOfertada|null>
     */
    public function disciplinasPorSlot(): array
    {
        return [
            'obrigatoria' => $this->disciplinaObrigatoria,
            'opcional_1' => $this->disciplinaOpcional1,
            'opcional_2' => $this->disciplinaOpcional2,
        ];
    }

    public function disciplinaNoSlot(string $slot): ?DisciplinaOfertada
    {
        return $this->disciplinasPorSlot()[$slot] ?? null;
    }

    public function sincronizarPagamentosDisciplinas(): void
    {
        if (! $this->isCandidatoSujeitoAPagamento()) {
            $this->pagamentos_disciplinas = null;

            return;
        }

        $pagamentos = [];
        $existentes = is_array($this->pagamentos_disciplinas) ? $this->pagamentos_disciplinas : [];

        foreach ($this->disciplinasPorSlot() as $slot => $disciplina) {
            if (! self::departamentoRequerPagamento($disciplina)) {
                continue;
            }

            $statusAtual = $existentes[$slot] ?? PagamentoDisciplinaStatus::Pendente->value;
            $pagamentos[$slot] = $statusAtual === PagamentoDisciplinaStatus::Pago->value
                ? PagamentoDisciplinaStatus::Pago->value
                : PagamentoDisciplinaStatus::Pendente->value;
        }

        $this->pagamentos_disciplinas = $pagamentos === [] ? null : $pagamentos;
    }

    public function temPagamentoPendente(): bool
    {
        if (! is_array($this->pagamentos_disciplinas)) {
            return false;
        }

        return in_array(PagamentoDisciplinaStatus::Pendente->value, $this->pagamentos_disciplinas, true);
    }

    public function primeiroSlotComPagamentoPendente(): ?string
    {
        if (! is_array($this->pagamentos_disciplinas)) {
            return null;
        }

        foreach (self::SLOTS_DISCIPLINA as $slot) {
            if (($this->pagamentos_disciplinas[$slot] ?? null) === PagamentoDisciplinaStatus::Pendente->value) {
                return $slot;
            }
        }

        return null;
    }

    /**
     * @return list<array{slot: string, disciplina: DisciplinaOfertada}>
     */
    public function disciplinasComPagamentoPendente(): array
    {
        if (! is_array($this->pagamentos_disciplinas)) {
            return [];
        }

        $result = [];
        foreach (self::SLOTS_DISCIPLINA as $slot) {
            if (($this->pagamentos_disciplinas[$slot] ?? null) !== PagamentoDisciplinaStatus::Pendente->value) {
                continue;
            }

            $disciplina = $this->disciplinaNoSlot($slot);
            if ($disciplina === null) {
                continue;
            }

            $result[] = ['slot' => $slot, 'disciplina' => $disciplina];
        }

        return $result;
    }

    public function pagamentoPendenteNoSlot(string $slot): bool
    {
        return ($this->pagamentos_disciplinas[$slot] ?? null) === PagamentoDisciplinaStatus::Pendente->value;
    }

    public function cpfCandidato(): ?string
    {
        $cpf = $this->dados_etapa_2['cpf'] ?? null;
        if (! is_string($cpf) && ! is_int($cpf)) {
            return null;
        }

        $digits = preg_replace('/\D/', '', (string) $cpf);

        return strlen($digits) === 11 ? $digits : null;
    }

    public function precisaGerarNovoPix(int $quantidadeAtual, float $valorAtual): bool
    {
        if ($this->pix_idfpix === null) {
            return true;
        }

        if ($this->pix_status === 'Expirado') {
            return true;
        }

        if ((int) $this->pix_quantidade_disciplinas !== $quantidadeAtual) {
            return true;
        }

        if ($this->pix_valor !== null && round((float) $this->pix_valor, 2) !== round($valorAtual, 2)) {
            return true;
        }

        return false;
    }

    public function marcarTodasDisciplinasPagas(): void
    {
        if (! is_array($this->pagamentos_disciplinas)) {
            return;
        }

        $pagamentos = [];
        foreach ($this->pagamentos_disciplinas as $slot => $status) {
            $pagamentos[$slot] = $status === PagamentoDisciplinaStatus::Pendente->value
                ? PagamentoDisciplinaStatus::Pago->value
                : $status;
        }

        $this->pagamentos_disciplinas = $pagamentos;
    }

    public function limparPix(): void
    {
        $this->pix_idfpix = null;
        $this->pix_status = null;
        $this->pix_valor = null;
        $this->pix_quantidade_disciplinas = null;
        $this->pix_qr_code = null;
        $this->pix_gerado_em = null;
        $this->pix_pago_em = null;
    }
}
