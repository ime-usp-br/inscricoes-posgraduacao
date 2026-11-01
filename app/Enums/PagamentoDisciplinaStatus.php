<?php

namespace App\Enums;

enum PagamentoDisciplinaStatus: string
{
    case Pendente = 'pendente';
    case AguardandoIsencao = 'aguardando_isencao';
    case Isento = 'isento';
    case Pago = 'pago';

    public function label(): string
    {
        return match ($this) {
            self::Pendente => 'Pendente',
            self::AguardandoIsencao => 'Aguardando isenção',
            self::Isento => 'Isento',
            self::Pago => 'Pago',
        };
    }
}
