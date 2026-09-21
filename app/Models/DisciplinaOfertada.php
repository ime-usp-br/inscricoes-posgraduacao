<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DisciplinaOfertada extends Model
{
    /** @use HasFactory<\Database\Factories\DisciplinaOfertadaFactory> */
    use HasFactory;

    protected $table = 'disciplinas_ofertadas';

    protected $fillable = [
        'periodo_id',
        'departamento',
        'codigo',
        'nome',
        'professor_nome',
        'professor_email',
    ];

    protected $appends = [
        'codigo_completo',
    ];

    public function getCodigoCompletoAttribute(): string
    {
        return strtoupper($this->departamento).str_pad((string) $this->codigo, 4, '0', STR_PAD_LEFT);
    }

    public static function normalizarEmail(?string $email): ?string
    {
        $normalizado = strtolower(trim((string) $email));

        return $normalizado === '' ? null : $normalizado;
    }

    /**
     * Único ponto que define "professor responsável": e-mail cadastrado na disciplina
     * igual ao e-mail do usuário (sem diferenciar maiúsculas/minúsculas).
     */
    public function professorResponsavelPor(User $user): bool
    {
        $esperado = self::normalizarEmail($this->professor_email);

        return $esperado !== null && $esperado === self::normalizarEmail($user->email);
    }

    /**
     * Mesma regra de {@see professorResponsavelPor()}, para uso em consultas.
     *
     * @param  Builder<DisciplinaOfertada>  $query
     * @return Builder<DisciplinaOfertada>
     */
    public function scopeResponsavelPor(Builder $query, User $user): Builder
    {
        $email = self::normalizarEmail($user->email);

        if ($email === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereRaw('LOWER(TRIM(professor_email)) = ?', [$email]);
    }

    /**
     * @phpstan-return BelongsTo<Periodo, $this>
     */
    public function periodo(): BelongsTo
    {
        return $this->belongsTo(Periodo::class);
    }
}

