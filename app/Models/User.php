<?php

namespace App\Models;

use App\Notifications\ResetPasswordNotification;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Log;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\Permission\Traits\HasRoles;
use Uspdev\SenhaunicaSocialite\Traits\HasSenhaunica;

class User extends Authenticatable implements Auditable, CanResetPassword, FilamentUser, MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    use HasRoles;
    use HasSenhaunica;
    use \OwenIt\Auditing\Auditable;

    /**
     * Attributes to exclude from the Audit.
     *
     * @var array<string>
     */
    protected $auditExclude = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'codpes',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Determine if the user can access the Filament panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() === 'admin') {
            return $this->isAdmin();
        }

        return true;
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('Admin');
    }

    public function canAccessSecretaria(): bool
    {
        return $this->hasAnyRole(['Admin', 'Secretario']);
    }

    public function canAccessProfessor(): bool
    {
        return $this->hasAnyRole(['Admin', 'Professor']);
    }

    public function canDeleteSecretariaResources(): bool
    {
        return $this->isAdmin();
    }

    /**
     * Sincroniza permissões da Senha Única sem derrubar o login se o Replicado falhar.
     *
     * @param  object  $userSenhaUnica
     */
    public function aplicarPermissoes($userSenhaUnica): void
    {
        if (! config('senhaunica.permission', false)) {
            return;
        }

        try {
            parent::aplicarPermissoes($userSenhaUnica);
        } catch (\Throwable $exception) {
            Log::warning('Permissões da Senha Única não sincronizadas (Replicado indisponível ou sem acesso).', [
                'codpes' => $this->codpes,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * Send the email verification notification.
     *
     * @return void
     */
    public function sendEmailVerificationNotification()
    {
        $this->notify(new \App\Notifications\EmailVerificationNotification);
    }

    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }
}
