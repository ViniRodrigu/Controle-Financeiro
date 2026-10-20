<?php

namespace App\Providers;

use App\Actions\Fortify\UpdateUserPassword;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
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
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::redirectUserForTwoFactorAuthenticationUsing(RedirectIfTwoFactorAuthenticatable::class);

        Fortify::loginView(fn () => view('auth.login'));
        Fortify::twoFactorChallengeView(fn () => view('auth.two-factor-challenge'));
        Fortify::confirmPasswordView(fn () => view('auth.confirm-password'));

        // Login só para usuários ativos. A mensagem de erro é sempre genérica (não revela se o e-mail existe).
        Fortify::authenticateUsing(function (Request $request) {
            $usuario = User::query()->where('email', Str::lower((string) $request->input('email')))->first();

            if (! $usuario) {
                // Gasta o mesmo tempo de um login real para não permitir descobrir e-mails pelo tempo de resposta.
                Hash::check((string) $request->input('password'), Hash::make(Str::random(32)));

                return null;
            }

            if ($usuario->ativo && Hash::check((string) $request->input('password'), $usuario->password)) {
                return $usuario;
            }

            return null;
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return [
                Limit::perMinute(5)->by($throttleKey),
                // Limite adicional por IP contra tentativas espalhadas em vários e-mails.
                Limit::perMinute(20)->by('ip|'.$request->ip()),
            ];
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });
    }
}
