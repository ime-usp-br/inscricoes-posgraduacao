<?php

namespace App\Enums;

enum InscricaoStatus: string
{
    case Inscrito = 'inscrito';
    case AprovadoSecretaria = 'aprovado_secretaria';
    case ReprovadoSecretaria = 'reprovado_secretaria';
    case AprovadoProfessor = 'aprovado_professor';
    case ReprovadoProfessor = 'reprovado_professor';

    public function label(): string
    {
        return match ($this) {
            self::Inscrito => 'Inscrito',
            self::AprovadoSecretaria => 'Aprovado pela Secretaria',
            self::ReprovadoSecretaria => 'Reprovado pela Secretaria',
            self::AprovadoProfessor => 'Aprovado pelo Professor',
            self::ReprovadoProfessor => 'Reprovado pelo Professor',
        };
    }
}
