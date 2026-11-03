<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfessorPendencyReminder extends Model
{
    protected $table = 'professor_pendency_reminders';

    protected $fillable = [
        'professor_email',
        'user_id',
        'inscricao_id',
        'disciplina_ofertada_id',
        'slot',
        'notified_at',
        'meta',
    ];

    protected $casts = [
        'notified_at' => 'datetime',
        'meta' => 'array',
    ];

    /**
     * @phpstan-return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @phpstan-return BelongsTo<Inscricao, $this>
     */
    public function inscricao(): BelongsTo
    {
        return $this->belongsTo(Inscricao::class);
    }

    /**
     * @phpstan-return BelongsTo<DisciplinaOfertada, $this>
     */
    public function disciplinaOfertada(): BelongsTo
    {
        return $this->belongsTo(DisciplinaOfertada::class);
    }
}
