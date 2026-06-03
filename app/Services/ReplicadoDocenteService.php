<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Throwable;
use Uspdev\Replicado\DB as ReplicadoDB;

class ReplicadoDocenteService
{
    /**
     * Verifica se o Número USP consta na tabela DOCENTE do Replicado.
     */
    public function ehDocente(int $codpes): bool
    {
        if ($codpes <= 0) {
            return false;
        }

        try {
            $registro = ReplicadoDB::fetch(
                'SELECT * FROM DOCENTE WHERE codpes = :codpes',
                ['codpes' => $codpes],
            );
        } catch (Throwable $exception) {
            Log::warning('Consulta à tabela DOCENTE do Replicado falhou.', [
                'codpes' => $codpes,
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        return is_array($registro) && $registro !== [];
    }
}
