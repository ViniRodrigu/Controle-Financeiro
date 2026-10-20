<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorias', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 80);
            $table->string('tipo', 10);
            $table->string('grupo', 60);
            $table->string('cor', 7)->default('#7b3f6e');
            $table->boolean('ativo')->default(true);
            $table->unsignedSmallInteger('ordem')->default(0);
            $table->timestamps();

            $table->unique(['nome', 'tipo']);
            $table->index(['tipo', 'ativo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categorias');
    }
};
