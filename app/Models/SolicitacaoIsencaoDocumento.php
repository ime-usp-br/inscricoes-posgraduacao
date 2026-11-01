<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class SolicitacaoIsencaoDocumento extends Model implements Auditable
{
    /** @use HasFactory<\Database\Factories\SolicitacaoIsencaoDocumentoFactory> */
    use AuditableTrait, HasFactory;

    protected $table = 'solicitacao_isencao_documentos';

    protected $fillable = [
        'solicitacao_isencao_id',
        'path',
        'nome_original',
        'tamanho',
        'mime',
    ];

    protected $casts = [
        'tamanho' => 'integer',
    ];

    /**
     * @phpstan-return BelongsTo<SolicitacaoIsencao, $this>
     */
    public function solicitacaoIsencao(): BelongsTo
    {
        return $this->belongsTo(SolicitacaoIsencao::class);
    }
}
