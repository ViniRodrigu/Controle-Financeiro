<?php

namespace App\Models;

use App\Enums\FormaPagamento;
use App\Enums\StatusLancamento;
use App\Enums\TipoLancamento;
use App\Models\Concerns\Auditavel;
use App\Services\CalculadoraLancamento;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

#[Fillable([
    'data', 'tipo', 'categoria_id', 'contato_id', 'descricao', 'forma_pagamento', 'status',
    'valor', 'parcelas_cartao', 'taxa_percentual', 'taxa_valor', 'valor_liquido', 'liquido_informado', 'valor_caixa',
    'grupo_parcelamento', 'parcela_numero', 'parcela_total', 'observacoes', 'criado_por', 'atualizado_por',
])]
class Lancamento extends Model
{
    use Auditavel, HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'data' => 'date',
            'tipo' => TipoLancamento::class,
            'status' => StatusLancamento::class,
            'forma_pagamento' => FormaPagamento::class,
            'valor' => 'decimal:2',
            'parcelas_cartao' => 'integer',
            'taxa_percentual' => 'decimal:3',
            'taxa_valor' => 'decimal:2',
            'valor_liquido' => 'decimal:2',
            'liquido_informado' => 'decimal:2',
            'valor_caixa' => 'decimal:2',
            'parcela_numero' => 'integer',
            'parcela_total' => 'integer',
        ];
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    public function contato(): BelongsTo
    {
        return $this->belongsTo(Contato::class)->withTrashed();
    }

    public function criador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'criado_por');
    }

    public function atualizador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'atualizado_por');
    }

    /** Demais lançamentos do mesmo parcelamento/recorrência. */
    public function parcelas(): HasMany
    {
        return $this->hasMany(self::class, 'grupo_parcelamento', 'grupo_parcelamento')->orderBy('parcela_numero');
    }

    public function ehEntrada(): bool
    {
        return $this->tipo === TipoLancamento::Entrada;
    }

    public function estaPendente(): bool
    {
        return $this->status === StatusLancamento::Pendente;
    }

    public function estaVencido(): bool
    {
        return $this->estaPendente() && $this->data?->lt(today());
    }

    /** Diferença entre o líquido informado e o líquido previsto pela taxa (coluna P da planilha). */
    public function diferenca(): ?string
    {
        return app(CalculadoraLancamento::class)->diferenca($this->valor, $this->taxa_percentual, $this->liquido_informado);
    }

    /** Percentual efetivamente descontado do valor bruto. */
    public function taxaEfetiva(): ?float
    {
        if ($this->taxa_valor === null || (float) $this->valor <= 0) {
            return null;
        }

        return round((float) $this->taxa_valor / (float) $this->valor * 100, 2);
    }

    #[Scope]
    protected function periodo(Builder $query, CarbonInterface|string $inicio, CarbonInterface|string $fim): void
    {
        // Intervalo semiaberto [início, fim + 1 dia) funciona igual em MySQL e SQLite.
        $query->where($query->qualifyColumn('data'), '>=', Carbon::parse($inicio)->toDateString())
            ->where($query->qualifyColumn('data'), '<', Carbon::parse($fim)->addDay()->toDateString());
    }

    /**
     * Aplica os filtros da listagem/exportação. Os valores já chegam validados.
     *
     * @param  array<string, mixed>  $filtros
     */
    #[Scope]
    protected function filtrar(Builder $query, array $filtros): void
    {
        $coluna = fn (string $nome) => $query->qualifyColumn($nome);

        $query
            ->when($filtros['de'] ?? null, fn (Builder $q, $de) => $q->where($coluna('data'), '>=', Carbon::parse($de)->toDateString()))
            ->when($filtros['ate'] ?? null, fn (Builder $q, $ate) => $q->where($coluna('data'), '<', Carbon::parse($ate)->addDay()->toDateString()))
            ->when($filtros['tipo'] ?? null, fn (Builder $q, $tipo) => $q->where($coluna('tipo'), $tipo))
            ->when($filtros['categoria_id'] ?? null, fn (Builder $q, $id) => $q->where($coluna('categoria_id'), $id))
            ->when($filtros['contato_id'] ?? null, fn (Builder $q, $id) => $q->where($coluna('contato_id'), $id))
            ->when($filtros['forma_pagamento'] ?? null, fn (Builder $q, $forma) => $q->where($coluna('forma_pagamento'), $forma))
            ->when($filtros['status'] ?? null, function (Builder $q, $status) use ($coluna) {
                if ($status === 'vencido') {
                    $q->where($coluna('status'), StatusLancamento::Pendente)->where($coluna('data'), '<', today()->toDateString());
                } else {
                    $q->where($coluna('status'), $status);
                }
            })
            ->when($filtros['busca'] ?? null, function (Builder $q, $termo) use ($coluna) {
                $q->where(function (Builder $q) use ($termo, $coluna) {
                    $q->where($coluna('descricao'), 'like', "%{$termo}%")
                        ->orWhereHas('contato', fn (Builder $c) => $c->where('nome', 'like', "%{$termo}%"));
                });
            });
    }
}
