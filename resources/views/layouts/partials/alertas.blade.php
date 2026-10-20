@if (session('sucesso'))
    <div class="alert alert-success suave-success alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
        <i class="bi bi-check-circle-fill"></i>
        <div>{{ session('sucesso') }}</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
    </div>
@endif

@if (session('erro'))
    <div class="alert alert-danger suave-danger alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <div>{{ session('erro') }}</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-danger suave-danger" role="alert">
        <div class="d-flex align-items-center gap-2 fw-semibold mb-1">
            <i class="bi bi-exclamation-triangle-fill"></i> Revise os campos destacados:
        </div>
        <ul class="mb-0 small">
            @foreach ($errors->all() as $erro)
                <li>{{ $erro }}</li>
            @endforeach
        </ul>
    </div>
@endif
