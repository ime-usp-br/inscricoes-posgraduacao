<?php

namespace App\Policies;

use App\Models\Inscricao;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class InscricaoPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Inscricao $inscricao): bool
    {
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Inscricao $inscricao): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Inscricao $inscricao): bool
    {
        return false;
    }

    /**
     * Professor só avalia o slot cuja disciplina ele é responsável. Admin avalia qualquer um.
     */
    public function avaliarDisciplinaComoProfessor(User $user, Inscricao $inscricao, string $slot): bool
    {
        if (! $user->canAccessProfessor()) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        $disciplina = $inscricao->disciplinaNoSlot($slot);

        return $disciplina !== null && $disciplina->professorResponsavelPor($user);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Inscricao $inscricao): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Inscricao $inscricao): bool
    {
        return false;
    }
}
