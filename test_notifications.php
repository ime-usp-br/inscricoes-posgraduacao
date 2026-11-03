<?php

require '/var/www/html/vendor/autoload.php';
$app = require_once '/var/www/html/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\DB;
use App\Models\SolicitacaoIsencao;
use App\Models\User;
use App\Notifications\SolicitacaoIsencaoRecebida;
use App\Notifications\SolicitacaoIsencaoEnviada;
use App\Notifications\SolicitacaoIsencaoDecidida;
use App\Notifications\EmailVerificationNotification;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\WelcomeNotification;
use App\Notifications\UserRegisteredNotification;
use App\Notifications\SystemAlertNotification;
use App\Enums\SolicitacaoIsencaoStatus;

DB::table('jobs')->truncate();
echo "Fila limpa\n";

$sol = SolicitacaoIsencao::with('inscricao')->latest()->first();
if ($sol && $sol->inscricao) {
    $email = $sol->inscricao->email;
    Notification::route('mail', $email)->notify(new SolicitacaoIsencaoRecebida($sol));
    echo "1) SolicitacaoIsencaoRecebida -> {$email}\n";
    Notification::route('mail', $email)->notify(new SolicitacaoIsencaoEnviada($sol));
    echo "2) SolicitacaoIsencaoEnviada -> {$email}\n";
    $solAprov = SolicitacaoIsencao::find($sol->id);
    $solAprov->status = SolicitacaoIsencaoStatus::Aprovada;
    Notification::route('mail', $email)->notify(new SolicitacaoIsencaoDecidida($solAprov));
    echo "3) SolicitacaoIsencaoDecidida (APROVADA) -> {$email} [SIGNED LINK]\n";
} else {
    echo "AVISO: Sem solicitacao/inscricao para testar isencao\n";
}

$user = User::first();
if ($user) {
    $user->notify(new EmailVerificationNotification());
    echo "4) EmailVerificationNotification (signed) -> {$user->email}\n";
    $user->notify(new ResetPasswordNotification('token-teste-123456'));
    echo "5) ResetPasswordNotification -> {$user->email}\n";
    $user->notify(new WelcomeNotification());
    echo "6) WelcomeNotification -> {$user->email}\n";
    $user->notify(new SystemAlertNotification('info', 'Teste de Alerta', ['origem' => 'teste']));
    echo "7) SystemAlertNotification -> {$user->email}\n";
    $admins = User::limit(2)->get();
    Notification::send($admins, new UserRegisteredNotification(['name' => $user->name, 'email' => $user->email]));
    echo "8) UserRegisteredNotification -> enviados para admins\n";
} else {
    echo "AVISO: Sem usuarios para testar\n";
}

echo "Total jobs: " . DB::table('jobs')->count() . "\n";
