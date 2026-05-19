<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DisciplinaOfertadaController;
use App\Http\Controllers\InscricaoAdminController;
use App\Http\Controllers\InscricaoProfessorController;
use App\Http\Controllers\InscricaoController;
use App\Http\Controllers\PeriodoController;

Route::get('/', [InscricaoController::class, 'home'])->name('home');
Route::post('/inscricao/etapa-1', [InscricaoController::class, 'storeEtapa1'])->name('inscricao.etapa1');
Route::post('/inscricao/etapa-2', [InscricaoController::class, 'storeEtapa2'])->name('inscricao.etapa2');
Route::post('/inscricao/etapa-3', [InscricaoController::class, 'storeEtapa3'])->name('inscricao.etapa3');
Route::post('/inscricao/reiniciar', [InscricaoController::class, 'reiniciar'])->name('inscricao.reiniciar');

Route::view('welcome', 'welcome')->name('welcome');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';

Route::middleware(['auth', 'verified', 'admin'])->group(function (): void {
    Route::view('/secretaria', 'secretaria.index')->name('secretaria');

    Route::resource('periodo', PeriodoController::class);
    Route::post('/periodo/confirmar', [PeriodoController::class, 'confirmar'])
        ->name('periodo.confirmar');
    Route::post('/periodo/salvar', [PeriodoController::class, 'salvar'])
        ->name('periodo.salvar');

    Route::resource('disciplina-ofertada', DisciplinaOfertadaController::class);

    Route::get('/inscricoes', [InscricaoAdminController::class, 'index'])->name('inscricoes.index');
    Route::get('/inscricoes/{inscricao}/arquivo/{campo}', [InscricaoAdminController::class, 'download'])
        ->where('campo', '[a-zA-Z0-9_]+')
        ->name('inscricoes.download');
    Route::get('/inscricoes/{inscricao}', [InscricaoAdminController::class, 'show'])->name('inscricoes.show');
    Route::post('/inscricoes/{inscricao}/aprovar-secretaria', [InscricaoAdminController::class, 'aprovarDisciplinaSecretaria'])
        ->name('inscricoes.aprovar-secretaria');
    Route::post('/inscricoes/{inscricao}/reprovar-secretaria', [InscricaoAdminController::class, 'reprovarDisciplinaSecretaria'])
        ->name('inscricoes.reprovar-secretaria');

    Route::view('/professor', 'professor.index')->name('professor');

    Route::get('/professor/inscricoes', [InscricaoProfessorController::class, 'index'])->name('professor.inscricoes.index');
    Route::get('/professor/inscricoes/{inscricao}', [InscricaoProfessorController::class, 'show'])->name('professor.inscricoes.show');
    Route::post('/professor/inscricoes/{inscricao}/aprovar', [InscricaoProfessorController::class, 'aprovarDisciplinaProfessor'])
        ->name('professor.inscricoes.aprovar');
    Route::post('/professor/inscricoes/{inscricao}/reprovar', [InscricaoProfessorController::class, 'reprovarDisciplinaProfessor'])
        ->name('professor.inscricoes.reprovar');
});