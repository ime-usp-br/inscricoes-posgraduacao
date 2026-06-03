<?php

namespace App\Enums;

enum ResumoPagamentoInscricao: string
{
    case NaoAplicavel = 'nao_aplicavel';
    case NaoPago = 'nao_pago';
    case Pago = 'pago';

    public function label(): string
    {
        return match ($this) {
            self::NaoAplicavel => 'Não aplicável',
            self::NaoPago => 'Não pago',
            self::Pago => 'Pago',
        };
    }
}
