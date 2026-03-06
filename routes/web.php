<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InscricaoController;
use App\Http\Controllers\PeriodoController;

Route::view('/', 'welcome');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';

Route::resource("inscricao", InscricaoController::class);
Route::resource("periodo", PeriodoController::class);

Route::post('/periodo/confirmar', [PeriodoController::class, 'confirmar'])
    ->name('periodo.confirmar');
Route::post('/periodo/salvar', [PeriodoController::class, 'salvar'])
    ->name('periodo.salvar');