<?php

namespace App\Models;

use App\Enums\TipoLancamento;
use App\Models\Concerns\Auditavel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nome', 'tipo', 'grupo', 'cor', 'ativo', 'ordem'])]
class Categoria extends Model
{
    use Auditavel, HasFactory;

    /** Grupos usados na planilha original (é possível criar outros). */
    public const GRUPOS = ['Receitas', 'Fixos', 'Variáveis', 'Experiência da paciente', 'Operacionais'];

    protected function casts(): array
    {
        return [
            'tipo' => TipoLancamento::class,
            'ativo' => 'boolean',
            'ordem' => 'integer',
        ];
    }

    public function lancamentos(): HasMany
    {
        return $this->hasMany(Lancamento::class);
    }

    #[Scope]
    protected function ativas(Builder $query): void
    {
        $query->where('ativo', true);
    }

    #[Scope]
    protected function ordenadas(Builder $query): void
    {
        $query->orderBy('tipo')->orderBy('grupo')->orderBy('ordem')->orderBy('nome');
    }
}
