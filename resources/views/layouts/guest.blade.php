<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('titulo', 'Acesso') · {{ config('app.name') }}</title>
    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
</head>
<body>
<main class="auth-pagina">
    <div class="auth-card">
        <div class="text-center mb-4">
            <span class="app-brand-icon d-inline-grid mb-3 text-white rounded-3 p-3 bg-primary shadow"><i class="bi bi-flower1 fs-4"></i></span>
            <h1 class="h4 mb-1">{{ config('financeiro.clinica_nome') }}</h1>
            <p class="text-muted mb-0">Controle financeiro</p>
        </div>

        <div class="card">
            <div class="card-body p-4">
                @yield('conteudo')
            </div>
        </div>

        <p class="text-center text-muted small mt-4 mb-0">
            <i class="bi bi-shield-lock me-1"></i>Acesso restrito. Todas as ações são registradas.
        </p>
    </div>
</main>
</body>
</html>
