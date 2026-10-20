<?php

namespace App\Console\Commands;

use App\Enums\Papel;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class CriarAdmin extends Command
{
    protected $signature = 'financeiro:criar-admin
        {--nome= : Nome do administrador}
        {--email= : E-mail de acesso}
        {--gerar-senha : Gera uma senha forte aleatória e exibe uma única vez}';

    protected $description = 'Cria um usuário administrador (use para o primeiro acesso ao sistema)';

    public function handle(): int
    {
        $nome = $this->option('nome') ?: text('Nome do administrador', required: true);
        $email = Str::lower(trim((string) ($this->option('email') ?: text('E-mail de acesso', required: true))));

        if ($this->option('gerar-senha')) {
            $senha = $this->gerarSenha();
        } else {
            $senha = password('Senha (mín. 10 caracteres, com maiúsculas, minúsculas, números e símbolos)', required: true);
            if ($senha !== password('Confirme a senha', required: true)) {
                $this->components->error('As senhas não conferem.');

                return self::FAILURE;
            }
        }

        $validacao = Validator::make(
            ['name' => $nome, 'email' => $email, 'password' => $senha],
            [
                'name' => ['required', 'string', 'max:120'],
                'email' => ['required', 'email:rfc', 'max:150', 'unique:users,email'],
                'password' => ['required', Password::default()],
            ],
        );

        if ($validacao->fails()) {
            foreach ($validacao->errors()->all() as $erro) {
                $this->components->error($erro);
            }

            return self::FAILURE;
        }

        User::create([
            'name' => $nome,
            'email' => $email,
            'password' => $senha,
            'papel' => Papel::Admin,
            'ativo' => true,
        ]);

        $this->components->info("Administrador {$email} criado.");

        if ($this->option('gerar-senha')) {
            $this->components->warn('Senha gerada (anote agora, ela não será exibida de novo):');
            $this->line("  {$senha}");
        }

        return self::SUCCESS;
    }

    private function gerarSenha(): string
    {
        // Garante ao menos um caractere de cada classe exigida pela política de senhas.
        $senha = Str::password(16).random_int(0, 9).'aZ#';

        return str_shuffle($senha);
    }
}
