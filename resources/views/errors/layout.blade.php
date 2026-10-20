<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('codigo') · {{ config('app.name') }}</title>
    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
</head>
<body>
<main class="auth-pagina">
    <div class="text-center">
        <div class="display-3 fw-bold text-primary mb-2">@yield('codigo')</div>
        <h1 class="h4 mb-2">@yield('titulo')</h1>
        <p class="text-muted mb-4">@yield('mensagem')</p>
        <a href="{{ url('/') }}" class="btn btn-primary"><i class="bi bi-house me-1"></i>Voltar ao início</a>
    </div>
</main>
</body>
</html>
