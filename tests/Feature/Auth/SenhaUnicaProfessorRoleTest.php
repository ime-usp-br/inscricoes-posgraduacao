<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\ReplicadoDocenteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\Fakes\FakeSenhaunicaSocialiteProvider;
use Tests\TestCase;

class SenhaUnicaProfessorRoleTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function login_senha_unica_atribui_role_professor_quando_codpes_esta_em_docente(): void
    {
        Role::create(['name' => 'Professor', 'guard_name' => 'web']);

        $codpes = 9876543;

        $this->mock(ReplicadoDocenteService::class, function ($mock) use ($codpes): void {
            $mock->shouldReceive('ehDocente')
                ->once()
                ->with($codpes)
                ->andReturn(true);
        });

        Socialite::shouldReceive('driver')
            ->with('senhaunica')
            ->andReturn(new FakeSenhaunicaSocialiteProvider([
                'codpes' => (string) $codpes,
                'nompes' => 'Docente Teste',
                'email' => 'docente.teste@usp.br',
            ]));

        $response = $this->get('/callback');

        $response->assertRedirect('/');

        $user = User::query()->where('codpes', $codpes)->first();

        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('Professor'));
    }

    #[Test]
    public function login_senha_unica_nao_atribui_role_professor_quando_codpes_nao_esta_em_docente(): void
    {
        Role::create(['name' => 'Professor', 'guard_name' => 'web']);

        $codpes = 1111111;

        $this->mock(ReplicadoDocenteService::class, function ($mock) use ($codpes): void {
            $mock->shouldReceive('ehDocente')
                ->once()
                ->with($codpes)
                ->andReturn(false);
        });

        Socialite::shouldReceive('driver')
            ->with('senhaunica')
            ->andReturn(new FakeSenhaunicaSocialiteProvider([
                'codpes' => (string) $codpes,
                'nompes' => 'Usuario Teste',
                'email' => 'usuario.teste@usp.br',
            ]));

        $this->get('/callback')->assertRedirect('/');

        $user = User::query()->where('codpes', $codpes)->first();

        $this->assertNotNull($user);
        $this->assertFalse($user->hasRole('Professor'));
    }
}
