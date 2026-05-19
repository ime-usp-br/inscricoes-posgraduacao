<?php

namespace App\Enums;

enum InscricaoStatus: string
{
    case Inscrito = 'inscrito';
    case AprovadoSecretaria = 'aprovado_secretaria';
    case ReprovadoSecretaria = 'reprovado_secretaria';

    public function label(): string
    {
        return match ($this) {
            self::Inscrito => 'Inscrito',
            self::AprovadoSecretaria => 'Aprovado pela Secretaria',
            self::ReprovadoSecretaria => 'Reprovado pela Secretaria',
        };
    }
}
