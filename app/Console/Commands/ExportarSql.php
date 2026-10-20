<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Connection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Gera um .sql completo (estrutura + dados) para importar pelo phpMyAdmin em MySQL 5.7+/8 ou MariaDB.
 * O arquivo pode ser importado mais de uma vez: cria só as tabelas que faltam e ignora registros já existentes.
 */
class ExportarSql extends Command
{
    protected $signature = 'financeiro:exportar-sql
        {arquivo? : Caminho do arquivo .sql (padrão: database/exportacoes/)}';

    protected $description = 'Exporta o banco em um .sql pronto para importar no phpMyAdmin';

    /** Tabelas temporárias do framework: vai só a estrutura (sessões e cache não devem mudar de servidor). */
    private const SOMENTE_ESTRUTURA = ['sessions', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs', 'password_reset_tokens'];

    /** Colunas que não devem sair do servidor de origem. */
    private const COLUNAS_ANULADAS = ['users' => ['remember_token']];

    private const LINHAS_POR_INSERT = 200;

    public function handle(): int
    {
        $conexao = DB::connection();

        if (! in_array($conexao->getDriverName(), ['mysql', 'mariadb'], true)) {
            $this->components->error('A exportação SQL só funciona com MySQL ou MariaDB.');

            return self::FAILURE;
        }

        $arquivo = $this->argument('arquivo')
            ?: database_path('exportacoes/'.Str::slug($conexao->getDatabaseName()).'-'.now()->format('Y-m-d-His').'.sql');
        File::ensureDirectoryExists(dirname($arquivo));

        // Datas/horas exportadas em UTC; o cabeçalho do arquivo faz o mesmo na importação.
        $fusoOriginal = $conexao->selectOne('SELECT @@session.time_zone AS fuso')->fuso;
        $conexao->statement("SET time_zone = '+00:00'");

        try {
            $tabelas = $this->ordenarPorDependencia($conexao, collect($conexao->select("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'"))
                ->map(fn (object $linha) => array_values((array) $linha)[0])
                ->sort()
                ->values());

            $saida = fopen($arquivo, 'wb');
            fwrite($saida, $this->cabecalho($conexao->getDatabaseName()));

            foreach ($tabelas as $tabela) {
                $estrutura = array_values((array) $conexao->selectOne("SHOW CREATE TABLE `{$tabela}`"))[1];
                fwrite($saida, "\n-- Estrutura: {$tabela}\n".$this->estruturaPortavel($estrutura).";\n");
            }

            $resumo = [];
            foreach ($tabelas->diff(self::SOMENTE_ESTRUTURA) as $tabela) {
                $resumo[] = [$tabela, $this->exportarDados($conexao, $saida, $tabela)];
            }

            fwrite($saida, "\nSET FOREIGN_KEY_CHECKS = 1;\n");
            fclose($saida);
        } finally {
            $conexao->statement('SET time_zone = '.$conexao->getPdo()->quote($fusoOriginal));
        }

        $this->table(['Tabela', 'Registros'], $resumo);
        $this->components->info('Arquivo gerado: '.$arquivo);
        $this->components->warn('Ele contém dados pessoais e hashes de senha: não envie para o git nem compartilhe.');

        return self::SUCCESS;
    }

    /**
     * Coloca as tabelas referenciadas por chave estrangeira antes das que dependem delas
     * (ex.: users antes de audit_logs). Assim a importação funciona mesmo que o phpMyAdmin
     * mantenha a verificação de chaves estrangeiras ligada.
     *
     * @param  Collection<int, string>  $tabelas
     * @return Collection<int, string>
     */
    private function ordenarPorDependencia(Connection $conexao, Collection $tabelas): Collection
    {
        $dependencias = collect($conexao->select(
            'SELECT DISTINCT TABLE_NAME AS tabela, REFERENCED_TABLE_NAME AS referencia
               FROM information_schema.KEY_COLUMN_USAGE
              WHERE TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME IS NOT NULL'
        ))->groupBy('tabela')->map(fn (Collection $linhas) => $linhas->pluck('referencia')->all());

        $ordenadas = [];
        $visitar = function (string $tabela, array $caminho = []) use (&$visitar, &$ordenadas, $dependencias): void {
            if (in_array($tabela, $ordenadas, true) || in_array($tabela, $caminho, true)) {
                return;
            }

            foreach ($dependencias->get($tabela, []) as $referencia) {
                $visitar($referencia, [...$caminho, $tabela]);
            }

            $ordenadas[] = $tabela;
        };

        $tabelas->each(fn (string $tabela) => $visitar($tabela));

        return collect($ordenadas)->intersect($tabelas)->values();
    }

    private function cabecalho(string $banco): string
    {
        return implode("\n", [
            '-- Controle Financeiro · exportação para importar no phpMyAdmin (MySQL 5.7+/8 ou MariaDB)',
            '-- Origem: banco `'.$banco.'` em '.now()->format('d/m/Y H:i'),
            '-- Importe em um banco VAZIO. Tabelas em ordem de dependência (funciona com a verificação',
            '-- de chaves estrangeiras ligada ou desligada). Pode ser importado mais de uma vez sem duplicar.',
            '-- ATENÇÃO: contém dados pessoais e hashes de senha.',
            '',
            'SET NAMES utf8mb4;',
            "SET time_zone = '+00:00';",
            'SET FOREIGN_KEY_CHECKS = 0;',
            "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';",
            '',
        ]);
    }

    /** Ajusta o CREATE TABLE do MariaDB para também funcionar no MySQL e não falhar se a tabela já existir. */
    private function estruturaPortavel(string $sql): string
    {
        $sql = preg_replace('/^CREATE TABLE /', 'CREATE TABLE IF NOT EXISTS ', $sql);
        $sql = preg_replace('/ AUTO_INCREMENT=\d+/', '', $sql);
        // No MariaDB, JSON aparece como longtext + CHECK; o tipo "json" é aceito pelos dois bancos.
        $sql = preg_replace('/longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin (DEFAULT NULL|NOT NULL) CHECK \(json_valid\(`\w+`\)\)/i', 'json $1', $sql);

        return str_ireplace('current_timestamp()', 'CURRENT_TIMESTAMP', $sql);
    }

    /** @param  resource  $saida */
    private function exportarDados(Connection $conexao, $saida, string $tabela): int
    {
        $total = 0;
        $pdo = $conexao->getPdo();
        $anuladas = self::COLUNAS_ANULADAS[$tabela] ?? [];
        $temId = $conexao->getSchemaBuilder()->hasColumn($tabela, 'id');
        $ordem = $temId ? 'id' : $conexao->getSchemaBuilder()->getColumnListing($tabela)[0];
        $aoDuplicar = $temId ? "\nON DUPLICATE KEY UPDATE `id` = `id`;\n" : ";\n";
        $comando = $temId ? 'INSERT INTO' : 'INSERT IGNORE INTO';

        $conexao->table($tabela)->orderBy($ordem)->chunk(self::LINHAS_POR_INSERT, function ($linhas) use ($saida, $tabela, $pdo, $anuladas, $aoDuplicar, $comando, &$total) {
            $colunas = array_keys((array) $linhas->first());

            $valores = $linhas->map(function (object $linha) use ($pdo, $anuladas) {
                $campos = [];
                foreach ((array) $linha as $coluna => $valor) {
                    $campos[] = match (true) {
                        $valor === null, in_array($coluna, $anuladas, true) => 'NULL',
                        is_int($valor), is_float($valor) => (string) $valor,
                        default => $pdo->quote((string) $valor),
                    };
                }

                return '('.implode(', ', $campos).')';
            });

            fwrite($saida, "\n{$comando} `{$tabela}` (`".implode('`, `', $colunas)."`) VALUES\n    "
                .$valores->implode(",\n    ")
                .$aoDuplicar);

            $total += $linhas->count();
        });

        return $total;
    }
}
