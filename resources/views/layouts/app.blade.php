<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@hasSection('titulo')@yield('titulo') · @endif{{ config('app.name') }}</title>
    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
</head>
<body>
<div class="app-shell">
    <aside class="app-sidebar d-none d-lg-flex">
        @include('layouts.partials.navegacao')
    </aside>

    <div class="offcanvas offcanvas-start app-sidebar-mobile d-lg-none" tabindex="-1" id="menuMobile" aria-label="Menu">
        <div class="d-flex flex-column h-100 overflow-auto">
            @include('layouts.partials.navegacao')
        </div>
    </div>

    <div class="app-main">
        <header class="app-topbar">
            <button class="btn btn-light d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#menuMobile" aria-label="Abrir menu">
                <i class="bi bi-list"></i>
            </button>

            <div class="ms-auto d-flex align-items-center gap-2">
                @can('editar')
                    <a href="{{ route('lancamentos.create') }}" class="btn btn-primary btn-sm d-none d-sm-inline-flex align-items-center gap-1">
                        <i class="bi bi-plus-lg"></i> Novo lançamento
                    </a>
                @endcan

                <div class="dropdown">
                    <button class="btn btn-link text-body text-decoration-none d-flex align-items-center gap-2 px-1" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="avatar">{{ auth()->user()->iniciais() }}</span>
                        <span class="d-none d-md-block text-start lh-sm">
                            <span class="d-block fw-semibold small">{{ auth()->user()->name }}</span>
                            <span class="d-block text-muted small">{{ auth()->user()->papel->rotulo() }}</span>
                        </span>
                        <i class="bi bi-chevron-down small text-muted"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        <li><a class="dropdown-item" href="{{ route('perfil') }}"><i class="bi bi-person me-2"></i>Meu perfil</a></li>
                        <li><a class="dropdown-item" href="{{ route('perfil.seguranca') }}"><i class="bi bi-shield-lock me-2"></i>Verificação em duas etapas</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2"></i>Sair</button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </header>

        <main class="app-content">
            @include('layouts.partials.alertas')
            @yield('conteudo')
        </main>
    </div>
</div>

@include('layouts.partials.modais')
</body>
</html>
