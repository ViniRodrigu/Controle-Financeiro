<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lancamentos', function (Blueprint $table) {
            $table->id();
            $table->date('data');
            $table->string('tipo', 10);
            $table->foreignId('categoria_id')->constrained('categorias')->restrictOnDelete();
            $table->foreignId('contato_id')->nullable()->constrained('contatos')->nullOnDelete();
            $table->string('descricao', 255)->nullable();
            $table->string('forma_pagamento', 20);
            $table->string('status', 10);

            // Valores monetários sempre em DECIMAL (nunca float).
            $table->decimal('valor', 12, 2);
            $table->unsignedTinyInteger('parcelas_cartao')->nullable();
            $table->decimal('taxa_percentual', 6, 3)->nullable();
            $table->decimal('taxa_valor', 12, 2)->nullable();
            $table->decimal('valor_liquido', 12, 2)->nullable();
            $table->decimal('liquido_informado', 12, 2)->nullable();
            // Valor que efetivamente entra/sai do caixa (base do painel e do resumo).
            $table->decimal('valor_caixa', 12, 2);

            // Parcelamento / recorrência gerado pelo sistema.
            $table->uuid('grupo_parcelamento')->nullable()->index();
            $table->unsignedSmallInteger('parcela_numero')->nullable();
            $table->unsignedSmallInteger('parcela_total')->nullable();

            $table->text('observacoes')->nullable();
            $table->foreignId('criado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('atualizado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('data');
            $table->index(['tipo', 'status', 'data']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lancamentos');
    }
};
