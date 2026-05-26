<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasAnyRole
{
    /**
     * Garante que o usuário autenticado possua ao menos uma das roles informadas.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user === null || $roles === [] || ! $user->hasAnyRole($roles)) {
            abort(403, 'Você não possui permissão para acessar esta área.');
        }

        /** @var \Symfony\Component\HttpFoundation\Response $response */
        $response = $next($request);

        return $response;
    }
}
