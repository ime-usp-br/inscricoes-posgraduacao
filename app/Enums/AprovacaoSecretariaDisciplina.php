<?php

namespace App\Enums;

enum AprovacaoSecretariaDisciplina: string
{
    case Aprovado = 'aprovado';

    public function label(): string
    {
        return match ($this) {
            self::Aprovado => 'Aprovado',
        };
    }
}
