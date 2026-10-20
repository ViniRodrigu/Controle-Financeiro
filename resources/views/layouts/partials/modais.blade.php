{{-- Modal genérico de confirmação (usado por formulários com data-confirmar). --}}
<div class="modal fade" id="modalConfirmacao" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-body p-4 text-center">
                <span class="kpi-icone suave-danger d-inline-grid mb-3 rounded-circle p-3"><i class="bi bi-exclamation-triangle fs-4"></i></span>
                <p class="mb-0" data-mensagem></p>
            </div>
            <div class="modal-footer border-0 pt-0 justify-content-center">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" data-confirmar-acao>Confirmar</button>
            </div>
        </div>
    </div>
</div>

@can('editar')
    {{-- Modal para dar baixa (marcar como pago/recebido). --}}
    <div class="modal fade" id="modalBaixa" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" class="modal-content">
                @csrf
                @method('PATCH')
                <div class="modal-header">
                    <h2 class="modal-title fs-5" data-baixa-titulo>Confirmar pagamento</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3" data-baixa-descricao></p>
                    <label for="data_pagamento" class="form-label">Data efetiva</label>
                    <input type="date" class="form-control" id="data_pagamento" name="data_pagamento" required>
                    <div class="form-text">A data do lançamento passa a ser a data efetiva, para o fluxo de caixa ficar no mês correto.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-check2-circle me-1"></i>Confirmar</button>
                </div>
            </form>
        </div>
    </div>
@endcan
