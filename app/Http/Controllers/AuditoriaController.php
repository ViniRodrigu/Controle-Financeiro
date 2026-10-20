<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AuditoriaController extends Controller
{
    public function __invoke(Request $request): View
    {
        $filtros = $request->validate([
            'user_id' => ['nullable', 'integer'],
            'acao' => ['nullable', Rule::in(array_keys(AuditLog::ACOES))],
            'de' => ['nullable', 'date'],
            'ate' => ['nullable', 'date'],
        ]);

        $registros = AuditLog::query()
            ->with('user')
            ->when($filtros['user_id'] ?? null, fn (Builder $q, $id) => $q->where('user_id', $id))
            ->when($filtros['acao'] ?? null, fn (Builder $q, $acao) => $q->where('acao', $acao))
            ->when($filtros['de'] ?? null, fn (Builder $q, $de) => $q->where('created_at', '>=', Carbon::parse($de)->startOfDay()))
            ->when($filtros['ate'] ?? null, fn (Builder $q, $ate) => $q->where('created_at', '<=', Carbon::parse($ate)->endOfDay()))
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        return view('auditoria.index', [
            'registros' => $registros,
            'filtros' => $filtros,
            'usuarios' => User::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
