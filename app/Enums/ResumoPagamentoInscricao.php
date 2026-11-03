<?php

namespace App\Enums;

enum ResumoPagamentoInscricao: string
{
    case NaoAplicavel = 'nao_aplicavel';
    case AguardandoIsencao = 'aguardando_isencao';
    case NaoPago = 'nao_pago';
    case Isento = 'isento';
    case Pago = 'pago';
    case PagoOuIsento = 'pago_ou_isento';

    public function label(): string
    {
        return match ($this) {
            self::NaoAplicavel => 'Não aplicável',
            self::AguardandoIsencao => 'Aguardando isenção',
            self::NaoPago => 'Não pago',
            self::Isento => 'Isento',
            self::Pago => 'Pago',
            self::PagoOuIsento => 'Pago/Isento',
        };
    }
}
