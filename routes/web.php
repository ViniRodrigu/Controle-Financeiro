<?php

use App\Http\Controllers\AuditoriaController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\ContatoController;
use App\Http\Controllers\LancamentoController;
use App\Http\Controllers\PainelController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\ResumoController;
use App\Http\Controllers\TaxaCartaoController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Route;

// Login, logout e 2FA são registrados pelo Laravel Fortify (config/fortify.php).

Route::redirect('/', '/painel');

Route::middleware(['auth', 'ativo'])->group(function () {
    Route::get('/painel', PainelController::class)->name('painel');
    Route::get('/resumo', ResumoController::class)->name('resumo');

    // Lançamentos (permissões por ação definidas no controller).
    Route::get('/lancamentos/exportar', [LancamentoController::class, 'exportar'])->name('lancamentos.exportar');
    Route::patch('/lancamentos/{lancamento}/baixar', [LancamentoController::class, 'baixar'])->name('lancamentos.baixar');
    Route::patch('/lancamentos/{lancamento}/restaurar', [LancamentoController::class, 'restaurar'])
        ->name('lancamentos.restaurar')->withTrashed();
    Route::resource('lancamentos', LancamentoController::class)->except('show');
    Route::get('/lancamentos/{lancamento}', [LancamentoController::class, 'show'])
        ->name('lancamentos.show')->withTrashed();

    Route::resource('contatos', ContatoController::class);

    Route::middleware('can:editar')->group(function () {
        Route::resource('categorias', CategoriaController::class)->except('show');
        Route::resource('taxas', TaxaCartaoController::class)->except('show');
    });

    Route::middleware('can:admin')->group(function () {
        Route::resource('usuarios', UsuarioController::class)->except(['show', 'destroy']);
        Route::get('/auditoria', AuditoriaController::class)->name('auditoria');
    });

    Route::get('/perfil', [PerfilController::class, 'show'])->name('perfil');
    Route::get('/perfil/seguranca', [PerfilController::class, 'seguranca'])
        ->middleware('password.confirm')->name('perfil.seguranca');
});
