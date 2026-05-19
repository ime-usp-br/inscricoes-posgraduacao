<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Periodo extends Model
{
    /** @use HasFactory<\Database\Factories\PeriodoFactory> */
    use HasFactory;

    protected $fillable = [
        'ano',
        'semestre',
        'data_inicio_inscricao',
        'data_fim_inscricao',
        'status',
    ];

    protected $casts = [
        'data_inicio_inscricao' => 'datetime',
        'data_fim_inscricao' => 'datetime',
    ];

    /**
     * @phpstan-return HasMany<DisciplinaOfertada, $this>
     */
    public function disciplinasOfertadas(): HasMany
    {
        return $this->hasMany(DisciplinaOfertada::class);
    }

    public static function ativoParaInscricoes(): ?self
    {
        return static::query()
            ->where('status', 'aberto')
            ->where('data_inicio_inscricao', '<=', now())
            ->where('data_fim_inscricao', '>=', now())
            ->orderByDesc('ano')
            ->orderByDesc('semestre')
            ->first();
    }
}

