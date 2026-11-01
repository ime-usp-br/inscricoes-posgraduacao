<?php

use App\Http\Controllers\DisciplinaOfertadaController;
use App\Http\Controllers\InscricaoAdminController;
use App\Http\Controllers\InscricaoController;
use App\Http\Controllers\InscricaoPagamentoController;
use App\Http\Controllers\InscricaoProfessorController;
use App\Http\Controllers\PeriodoController;
use App\Http\Controllers\SolicitacaoIsencaoAdminController;
use App\Http\Controllers\SolicitacaoIsencaoController;
use App\Http\Controllers\WspixWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', [InscricaoController::class, 'home'])->name('home');
Route::post('/inscricao/etapa-1', [InscricaoController::class, 'storeEtapa1'])->name('inscricao.etapa1');
Route::post('/inscricao/etapa-2', [InscricaoController::class, 'storeEtapa2'])->name('inscricao.etapa2');
Route::post('/inscricao/etapa-3', [InscricaoController::class, 'storeEtapa3'])->name('inscricao.etapa3');
Route::post('/inscricao/reiniciar', [InscricaoController::class, 'reiniciar'])->name('inscricao.reiniciar');
Route::get('/inscricao/pagamento', [InscricaoPagamentoController::class, 'show'])->name('inscricao.pagamento');
Route::get('/inscricao/pagamento/assinada/{inscricao}', [InscricaoPagamentoController::class, 'showAssinada'])
    ->middleware('signed')
    ->name('inscricao.pagamento.assinada');
Route::post('/inscricao/pagamento/gerar', [InscricaoPagamentoController::class, 'gerar'])->name('inscricao.pagamento.gerar');
Route::post('/inscricao/pagamento/verificar', [InscricaoPagamentoController::class, 'verificar'])->name('inscricao.pagamento.verificar');
Route::get('/inscricao/isencao', [SolicitacaoIsencaoController::class, 'show'])->name('inscricao.isencao.show');
Route::get('/inscricao/isencao/editar', [SolicitacaoIsencaoController::class, 'edit'])->name('inscricao.isencao.edit');
Route::post('/inscricao/isencao', [SolicitacaoIsencaoController::class, 'store'])->name('inscricao.isencao.store');
Route::put('/inscricao/isencao', [SolicitacaoIsencaoController::class, 'update'])->name('inscricao.isencao.update');
Route::post('/inscricao/isencao/cancelar', [SolicitacaoIsencaoController::class, 'cancelar'])->name('inscricao.isencao.cancelar');
Route::get('/inscricao/isencao/documento/{documento}', [SolicitacaoIsencaoController::class, 'documento'])
    ->name('inscricao.isencao.documento');
Route::get('/inscricao/pagamento/{slot}', [InscricaoPagamentoController::class, 'redirectLegado'])
    ->whereIn('slot', ['obrigatoria', 'opcional_1', 'opcional_2']);
Route::post('/webhooks/wspix', WspixWebhookController::class)->name('webhooks.wspix');

Route::view('welcome', 'welcome')->name('welcome');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';

Route::middleware(['auth', 'verified', 'role.any:Admin,Secretario'])->group(function (): void {
    Route::view('/secretaria', 'secretaria.index')->name('secretaria');

    Route::resource('periodo', PeriodoController::class);
    Route::post('/periodo/confirmar', [PeriodoController::class, 'confirmar'])
        ->name('periodo.confirmar');
    Route::post('/periodo/salvar', [PeriodoController::class, 'salvar'])
        ->name('periodo.salvar');

    Route::resource('disciplina-ofertada', DisciplinaOfertadaController::class);

    Route::get('/inscricoes', [InscricaoAdminController::class, 'index'])->name('inscricoes.index');
    Route::get('/inscricoes/relatorio', [InscricaoAdminController::class, 'relatorio'])
        ->name('inscricoes.relatorio');
    Route::post('/inscricoes/aprovar-bloco-secretaria', [InscricaoAdminController::class, 'aprovarEmBlocoSecretaria'])
        ->name('inscricoes.aprovar-bloco-secretaria');
    Route::get('/inscricoes/{inscricao}/arquivo/{campo}', [InscricaoAdminController::class, 'download'])
        ->where('campo', '[a-zA-Z0-9_]+')
        ->name('inscricoes.download');
    Route::get('/inscricoes/{inscricao}', [InscricaoAdminController::class, 'show'])->name('inscricoes.show');
    Route::post('/inscricoes/{inscricao}/aprovar-secretaria', [InscricaoAdminController::class, 'aprovarDisciplinaSecretaria'])
        ->name('inscricoes.aprovar-secretaria');
    Route::post('/inscricoes/{inscricao}/reprovar-secretaria', [InscricaoAdminController::class, 'reprovarDisciplinaSecretaria'])
        ->name('inscricoes.reprovar-secretaria');

    Route::post('/inscricoes/{inscricao}/isencao/decidir', [SolicitacaoIsencaoAdminController::class, 'decidir'])
        ->name('inscricoes.isencao.decidir');
    Route::post('/inscricoes/{inscricao}/isencao/estender', [SolicitacaoIsencaoAdminController::class, 'estender'])
        ->name('inscricoes.isencao.estender');

});

Route::middleware(['auth', 'verified', 'role.any:Admin,Professor'])->group(function (): void {
    Route::view('/professor', 'professor.index')->name('professor');

    Route::get('/professor/inscricoes', [InscricaoProfessorController::class, 'index'])->name('professor.inscricoes.index');
    Route::get('/professor/inscricoes/{inscricao}/arquivo/{campo}', [InscricaoProfessorController::class, 'download'])
        ->where('campo', '[a-zA-Z0-9_]+')
        ->name('professor.inscricoes.download');
    Route::get('/professor/inscricoes/{inscricao}', [InscricaoProfessorController::class, 'show'])->name('professor.inscricoes.show');
    Route::post('/professor/inscricoes/{inscricao}/aprovar', [InscricaoProfessorController::class, 'aprovarDisciplinaProfessor'])
        ->name('professor.inscricoes.aprovar');
    Route::post('/professor/inscricoes/{inscricao}/reprovar', [InscricaoProfessorController::class, 'reprovarDisciplinaProfessor'])
        ->name('professor.inscricoes.reprovar');
});
