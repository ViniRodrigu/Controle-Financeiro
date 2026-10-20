<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taxas_cartao', function (Blueprint $table) {
            $table->id();
            $table->string('forma_pagamento', 20);
            $table->unsignedTinyInteger('parcelas')->default(1);
            // Percentual (ex.: 3.050 = 3,05%). Nulo = taxa ainda não informada.
            $table->decimal('taxa_percentual', 6, 3)->nullable();
            $table->string('observacao', 255)->nullable();
            $table->timestamps();

            $table->unique(['forma_pagamento', 'parcelas']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taxas_cartao');
    }
};
