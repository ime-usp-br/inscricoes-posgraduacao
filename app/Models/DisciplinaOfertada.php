<?php

namespace App\Models;

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

    /**
     * @phpstan-return BelongsTo<Periodo, $this>
     */
    public function periodo(): BelongsTo
    {
        return $this->belongsTo(Periodo::class);
    }
}

