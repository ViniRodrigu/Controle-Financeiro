<?php

namespace App\Providers;

use App\Models\Categoria;
use App\Models\Contato;
use App\Models\Lancamento;
use App\Models\TaxaCartao;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Em desenvolvimento, acusa N+1 e atributos inexistentes; em produção nunca interrompe.
        Model::shouldBeStrict(! $this->app->isProduction());

        Relation::enforceMorphMap([
            'usuario' => User::class,
            'lancamento' => Lancamento::class,
            'categoria' => Categoria::class,
            'contato' => Contato::class,
            'taxa_cartao' => TaxaCartao::class,
        ]);

        // Perfis de acesso.
        Gate::define('admin', fn (User $user) => $user->ativo && $user->ehAdmin());
        Gate::define('editar', fn (User $user) => $user->ativo && $user->podeEditar());

        Password::defaults(function () {
            $regra = Password::min(10)->letters()->mixedCase()->numbers()->symbols();

            // Em produção também recusa senhas que já vazaram (consulta anônima ao Have I Been Pwned).
            return $this->app->isProduction() ? $regra->uncompromised() : $regra;
        });

        Route::resourceVerbs(['create' => 'novo', 'edit' => 'editar']);
        Paginator::useBootstrapFive();
    }
}
