@php
    $item = fn (string $rota, string $padrao, string $icone, string $rotulo) => [
        'url' => route($rota),
        'ativo' => request()->routeIs($padrao),
        'icone' => $icone,
        'rotulo' => $rotulo,
    ];

    $secoes = [
        'Visão geral' => [
            $item('painel', 'painel', 'bi-grid-1x2', 'Painel'),
            $item('resumo', 'resumo', 'bi-calendar3', 'Resumo mensal'),
        ],
        'Movimentações' => [
            $item('lancamentos.index', 'lancamentos.*', 'bi-arrow-left-right', 'Lançamentos'),
            $item('contatos.index', 'contatos.*', 'bi-people', 'Pacientes e fornecedores'),
        ],
    ];

    if (auth()->user()->can('editar')) {
        $secoes['Cadastros'] = [
            $item('categorias.index', 'categorias.*', 'bi-tags', 'Categorias'),
            $item('taxas.index', 'taxas.*', 'bi-percent', 'Taxas da maquininha'),
        ];
    }

    if (auth()->user()->can('admin')) {
        $secoes['Administração'] = [
            $item('usuarios.index', 'usuarios.*', 'bi-person-gear', 'Usuários'),
            $item('auditoria', 'auditoria', 'bi-shield-check', 'Auditoria'),
        ];
    }
@endphp

<a href="{{ route('painel') }}" class="app-brand">
    <span class="app-brand-icon"><i class="bi bi-flower1"></i></span>
    <span>
        <span class="d-block app-brand-nome">{{ config('financeiro.clinica_nome') }}</span>
        <span class="d-block app-brand-sub">Controle financeiro</span>
    </span>
</a>

<nav class="app-nav" aria-label="Menu principal">
    @foreach ($secoes as $titulo => $itens)
        <div class="app-nav-titulo">{{ $titulo }}</div>
        <ul class="nav flex-column gap-1">
            @foreach ($itens as $link)
                <li class="nav-item">
                    <a href="{{ $link['url'] }}" @class(['nav-link', 'active' => $link['ativo']]) @if ($link['ativo']) aria-current="page" @endif>
                        <i class="bi {{ $link['icone'] }}"></i> {{ $link['rotulo'] }}
                    </a>
                </li>
            @endforeach
        </ul>
    @endforeach
</nav>

<div class="app-sidebar-rodape">
    <i class="bi bi-lock me-1"></i> Conexão protegida · {{ now()->format('Y') }}
</div>
