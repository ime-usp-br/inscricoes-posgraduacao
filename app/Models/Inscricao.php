<?php

namespace App\Models;

use App\Enums\AprovacaoSecretariaDisciplina;
use App\Enums\InscricaoStatus;
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
    ];

    protected $casts = [
        'aluno_usp' => 'boolean',
        'dados_etapa_2' => 'array',
        'concluido_em' => 'datetime',
        'status' => InscricaoStatus::class,
        'aprovacao_obrigatoria_secretaria' => AprovacaoSecretariaDisciplina::class,
        'aprovacao_opcional_1_secretaria' => AprovacaoSecretariaDisciplina::class,
        'aprovacao_opcional_2_secretaria' => AprovacaoSecretariaDisciplina::class,
    ];

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

    public function sincronizarStatusSecretaria(): void
    {
        $disciplinas = $this->disciplinasParaAprovacaoSecretaria();

        if ($disciplinas === []) {
            $this->status = InscricaoStatus::Inscrito;

            return;
        }

        $todasAprovadas = collect($disciplinas)->every(
            fn (array $item): bool => $this->{$item['aprovacao']} === AprovacaoSecretariaDisciplina::Aprovado
        );

        $this->status = $todasAprovadas
            ? InscricaoStatus::AprovadoSecretaria
            : InscricaoStatus::Inscrito;
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
