<?php

namespace App\Enums;

enum ResumoAprovacaoInscricao: string
{
    case Pendente = 'pendente';
    case Aprovada = 'aprovada';
    case Reprovada = 'reprovada';
    case NaoAplicavel = 'nao_aplicavel';

    public function label(string $etapa): string
    {
        return match ($this) {
            self::Pendente => 'Pendente',
            self::Aprovada => $etapa === 'professor' ? 'Aprovada pelo Professor' : 'Aprovada pela Secretaria',
            self::Reprovada => $etapa === 'professor' ? 'Reprovada pelo Professor' : 'Reprovada pela Secretaria',
            self::NaoAplicavel => '—',
        };
    }
}
