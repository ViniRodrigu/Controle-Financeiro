<?php

namespace App\Models;

use App\Enums\TipoContato;
use App\Enums\TipoLancamento;
use App\Models\Concerns\Auditavel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable(['nome', 'tipo', 'telefone', 'email', 'observacoes', 'ativo'])]
class Contato extends Model
{
    use Auditavel, HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'tipo' => TipoContato::class,
            'ativo' => 'boolean',
        ];
    }

    public function lancamentos(): HasMany
    {
        return $this->hasMany(Lancamento::class);
    }

    /**
     * Localiza o paciente/fornecedor pelo nome (sem diferenciar maiúsculas) ou cria um novo.
     */
    public static function encontrarOuCriar(?string $nome, TipoLancamento $tipoLancamento): ?self
    {
        $nome = Str::squish((string) $nome);

        if ($nome === '') {
            return null;
        }

        return static::query()->whereRaw('LOWER(nome) = ?', [mb_strtolower($nome)])->first()
            ?? static::create([
                'nome' => $nome,
                'tipo' => TipoContato::paraTipoLancamento($tipoLancamento),
            ]);
    }
}
