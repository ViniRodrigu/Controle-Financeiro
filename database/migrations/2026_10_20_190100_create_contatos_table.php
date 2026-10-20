<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contatos', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 120);
            $table->string('tipo', 20);
            $table->string('telefone', 30)->nullable();
            $table->string('email', 150)->nullable();
            $table->text('observacoes')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('nome');
            $table->index('tipo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contatos');
    }
};
