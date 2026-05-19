<?php

namespace App\Models;

use App\Enums\AprovacaoProfessorDisciplina;
use App\Enums\AprovacaoSecretariaDisciplina;
use App\Enums\InscricaoStatus;
use App\Enums\ResumoAprovacaoInscricao;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Inscricao extends Model
{
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
        'disciplina_opcional_1_id',
        'disciplina_opcional_2_id',
        'status',
        'aprovacao_obrigatoria_secretaria',
        'aprovacao_opcional_1_secretaria',
        'aprovacao_opcional_2_secretaria',
        'aprovacao_obrigatoria_professor',
        'aprovacao_opcional_1_professor',
        'aprovacao_opcional_2_professor',
    ];

    protected $casts = [
        'aluno_usp' => 'boolean',
        'dados_etapa_2' => 'array',
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
     * @return list<array{slot: string, disciplina: DisciplinaOfertada, aprovacao: string}>
     */
    public function disciplinasParaAprovacaoSecretaria(): array
    {
        $slots = [
            'obrigatoria' => [
                'disciplina' => $this->disciplinaObrigatoria,
                'aprovacao' => 'aprovacao_obrigatoria_secretaria',
            ],
            'opcional_1' => [
                'disciplina' => $this->disciplinaOpcional1,
                'aprovacao' => 'aprovacao_opcional_1_secretaria',
            ],
            'opcional_2' => [
                'disciplina' => $this->disciplinaOpcional2,
                'aprovacao' => 'aprovacao_opcional_2_secretaria',
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
     * @param  list<array{slot: string, disciplina: DisciplinaOfertada, aprovacao: string}>  $disciplinas
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
     * @return list<array{slot: string, disciplina: DisciplinaOfertada, aprovacao: string}>
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
}
