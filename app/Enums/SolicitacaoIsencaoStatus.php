<?php

namespace App\Enums;

enum SolicitacaoIsencaoStatus: string
{
    case Pendente = 'pendente';
    case Aprovada = 'aprovada';
    case Reprovada = 'reprovada';
    case Cancelada = 'cancelada';

    public function label(): string
    {
        return match ($this) {
            self::Pendente => 'Pendente',
            self::Aprovada => 'Aprovada',
            self::Reprovada => 'Reprovada',
            self::Cancelada => 'Cancelada',
        };
    }
}
