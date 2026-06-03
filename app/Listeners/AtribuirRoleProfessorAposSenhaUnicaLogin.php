<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\ReplicadoDocenteService;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;
use Uspdev\SenhaunicaSocialite\Events\SenhaunicaUsuarioLogado;

class AtribuirRoleProfessorAposSenhaUnicaLogin
{
    public function __construct(
        private readonly ReplicadoDocenteService $replicadoDocenteService,
    ) {}

    public function handle(SenhaunicaUsuarioLogado $event): void
    {
        $user = $event->getUser();

        if (! $user instanceof User || ! $user->codpes) {
            return;
        }

        if ($user->hasRole('Professor')) {
            return;
        }

        try {
            if (! $this->replicadoDocenteService->ehDocente((int) $user->codpes)) {
                return;
            }

            Role::findOrCreate('Professor', 'web');
            $user->assignRole('Professor');

            Log::info('Papel Professor atribuído após login via Senha Única (registro na tabela DOCENTE do Replicado).', [
                'user_id' => $user->id,
                'codpes' => $user->codpes,
            ]);
        } catch (\Throwable $exception) {
            Log::warning('Papel Professor não atribuído: Replicado indisponível ou erro na consulta DOCENTE.', [
                'user_id' => $user->id,
                'codpes' => $user->codpes,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
