<?php

namespace App\Models;

use App\Enums\SolicitacaoIsencaoStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class SolicitacaoIsencao extends Model implements Auditable
{
    /** @use HasFactory<\Database\Factories\SolicitacaoIsencaoFactory> */
    use AuditableTrait, HasFactory, Notifiable;

    protected $table = 'solicitacoes_isencao';

    protected $fillable = [
        'inscricao_id',
        'status',
        'justificativa',
        'disciplinas_snapshot',
        'resposta_secretaria',
        'motivo_reprovacao',
        'decidida_por',
        'decidida_em',
        'prazo_pagamento_em',
        'prazo_pagamento_estendido_em',
        'lembrete_agendado_para',
        'lembrete_enviado_em',
        'cancelada_em',
        'motivo_cancelamento',
    ];

    protected $casts = [
        'status' => SolicitacaoIsencaoStatus::class,
        'disciplinas_snapshot' => 'array',
        'decidida_em' => 'datetime',
        'prazo_pagamento_em' => 'datetime',
        'prazo_pagamento_estendido_em' => 'datetime',
        'lembrete_agendado_para' => 'datetime',
        'lembrete_enviado_em' => 'datetime',
        'cancelada_em' => 'datetime',
    ];

    /**
     * @phpstan-return BelongsTo<Inscricao, $this>
     */
    public function inscricao(): BelongsTo
    {
        return $this->belongsTo(Inscricao::class);
    }

    /**
     * @phpstan-return BelongsTo<User, $this>
     */
    public function decididaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decidida_por');
    }

    public function routeNotificationForMail(object $notification): ?string
    {
        return $this->inscricao?->email;
    }

    /**
     * @phpstan-return HasMany<SolicitacaoIsencaoDocumento, $this>
     */
    public function documentos(): HasMany
    {
        return $this->hasMany(SolicitacaoIsencaoDocumento::class);
    }
}
