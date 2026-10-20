<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Dados de referência do sistema. O primeiro administrador é criado com
     * "php artisan financeiro:criar-admin" (nunca há senha padrão no código).
     */
    public function run(): void
    {
        $this->call([
            CategoriaSeeder::class,
            TaxaCartaoSeeder::class,
        ]);
    }
}
