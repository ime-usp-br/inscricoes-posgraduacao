<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Periodo extends Model
{
    /** @use HasFactory<\Database\Factories\PeriodoFactory> */
    use HasFactory;

    public const BRASILIA_TIMEZONE = 'America/Sao_Paulo';

    protected $fillable = [
        'ano',
        'semestre',
        'data_inicio_inscricao',
        'data_fim_inscricao',
    ];

    protected $casts = [
        'data_inicio_inscricao' => 'datetime',
        'data_fim_inscricao' => 'datetime',
    ];

    public function getStatusAttribute(mixed $value): string
    {
        return $this->inscricoesAbertas() ? 'aberto' : 'fechado';
    }

    public function setDataInicioInscricaoAttribute(mixed $value): void
    {
        $this->attributes['data_inicio_inscricao'] = $this->normalizarLimiteInscricao($value, false);
    }

    public function setDataFimInscricaoAttribute(mixed $value): void
    {
        $this->attributes['data_fim_inscricao'] = $this->normalizarLimiteInscricao($value, true);
    }

    /**
     * @phpstan-return HasMany<DisciplinaOfertada, $this>
     */
    public function disciplinasOfertadas(): HasMany
    {
        return $this->hasMany(DisciplinaOfertada::class);
    }

    public function inscricoesAbertas(?Carbon $momento = null): bool
    {
        if ($this->data_inicio_inscricao === null || $this->data_fim_inscricao === null) {
            return false;
        }

        $agora = ($momento ?? now(self::BRASILIA_TIMEZONE))->copy()->setTimezone(self::BRASILIA_TIMEZONE);
        $inicio = $this->data_inicio_inscricao->copy()->setTimezone(self::BRASILIA_TIMEZONE);
        $fim = $this->data_fim_inscricao->copy()->setTimezone(self::BRASILIA_TIMEZONE);

        return $inicio->lte($agora) && $fim->gte($agora);
    }

    public function scopeAbertos(Builder $query, ?Carbon $momento = null): Builder
    {
        $agora = ($momento ?? now(self::BRASILIA_TIMEZONE))->copy()->setTimezone(self::BRASILIA_TIMEZONE);

        return $query
            ->where('data_inicio_inscricao', '<=', $agora)
            ->where('data_fim_inscricao', '>=', $agora);
    }

    public static function ativoParaInscricoes(): ?self
    {
        return static::query()
            ->abertos()
            ->orderByDesc('ano')
            ->orderByDesc('semestre')
            ->first();
    }

    private function normalizarLimiteInscricao(mixed $value, bool $fimDoDia): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $data = $value instanceof Carbon
            ? $value->copy()->setTimezone(self::BRASILIA_TIMEZONE)
            : Carbon::parse($value, self::BRASILIA_TIMEZONE);

        $data = $fimDoDia ? $data->endOfDay() : $data->startOfDay();

        return $data->toDateTimeString();
    }
}

