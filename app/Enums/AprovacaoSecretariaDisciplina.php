<?php

namespace App\Enums;

enum AprovacaoSecretariaDisciplina: string
{
    case Aprovado = 'aprovado';
    case Reprovado = 'reprovado';

    public function label(): string
    {
        return match ($this) {
            self::Aprovado => 'Aprovado',
            self::Reprovado => 'Reprovado',
        };
    }
}
