# Controle Financeiro · Clínica

Sistema web de controle financeiro para clínica de estética, criado a partir da planilha
`Controle_Financeiro_Clinica.xlsx` e ampliado com perfis de acesso, auditoria e relatórios.

## Stack

| Camada | Tecnologia |
| --- | --- |
| Back-end | PHP 8.3+ · Laravel 13 · Laravel Fortify (login e verificação em duas etapas) |
| Front-end | Blade · Bootstrap 5.3 · Bootstrap Icons · Chart.js · Vite |
| Banco de dados | MySQL 8 (utf8mb4) |
| Testes | PHPUnit (53 testes de cálculo, segurança, permissões, relatórios e importação) |

## Funcionalidades

- **Painel**: receita recebida, despesas, lucro, margem, faturamento bruto, taxas de cartão,
  valores a receber e a pagar, comparação com o período anterior, gráficos e pendências.
- **Lançamentos**: entradas e saídas com filtros, busca, totais, exportação CSV (abre no Excel),
  duplicação, baixa de pendências, lixeira com restauração e histórico de alterações.
- **Taxas da maquininha**: a taxa é sugerida pela tabela (modalidade + parcelas) e o líquido é
  calculado no servidor. O "líquido recebido", quando informado, prevalece (mesma regra da planilha).
- **Parcelamento e recorrência**: gera N lançamentos mensais ou semanais, dividindo ou repetindo o valor.
- **Resumo mensal**: mês a mês e matriz anual por categoria/grupo (regime de caixa), pronto para imprimir.
- **Pacientes e fornecedores**: criados automaticamente ao lançar, com histórico e ticket médio.
- **Categorias** e **taxas** editáveis; **usuários** com 3 perfis; **auditoria** de tudo.

### Diferenças em relação à planilha

- O "Resumo mensal" da planilha comparava o número do mês (9) com o nome ("Setembro") e por isso
  mostrava todos os meses zerados. No sistema os valores aparecem corretamente.
- No Painel da planilha, "Sistemas / assinaturas" tinha **R$ 276,00 digitados à mão**, sem lançamento
  correspondente. O sistema calcula tudo a partir dos lançamentos: se essa despesa existiu, lance-a.
- Taxas digitadas manualmente que não batiam com o percentual (ex.: R$ 131,66 em vez de 11%) foram
  importadas como "líquido recebido", preservando exatamente o valor que entrou no caixa.

## Perfis de acesso

| Perfil | Pode |
| --- | --- |
| Administrador | Tudo, incluindo usuários, auditoria e restaurar lançamentos excluídos |
| Financeiro | Lançamentos, pacientes/fornecedores, categorias e taxas |
| Somente leitura | Consultar painel, relatórios, lançamentos e exportar |

## Instalação (Windows + Laravel Herd)

1. **MySQL**: no Herd Pro, abra *Services* → adicione **MySQL** e inicie. (Ou instale o MySQL 8.)
2. Crie o banco e um usuário exclusivo (troque a senha):

   ```sql
   CREATE DATABASE controle_financeiro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE USER 'controle_financeiro'@'localhost' IDENTIFIED BY 'troque-por-uma-senha-forte';
   GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, DROP, REFERENCES
         ON controle_financeiro.* TO 'controle_financeiro'@'localhost';
   ```

3. Configure o ambiente e instale:

   ```bash
   composer install
   copy .env.example .env
   php artisan key:generate
   ```

   Preencha `DB_PASSWORD` (e `CLINICA_NOME`, se quiser) no `.env`.

4. Crie as tabelas, as categorias e a tabela de taxas da planilha:

   ```bash
   php artisan migrate --seed
   ```

5. Crie o primeiro administrador (a senha é digitada de forma oculta; não existe senha padrão):

   ```bash
   php artisan financeiro:criar-admin
   ```

6. Importe os lançamentos da planilha (simule antes; pode rodar de novo sem duplicar):

   ```bash
   php artisan financeiro:importar-planilha "C:\caminho\Controle_Financeiro_Clinica.xlsx" --simular
   php artisan financeiro:importar-planilha "C:\caminho\Controle_Financeiro_Clinica.xlsx"
   ```

7. Compile o front-end e acesse `http://controle-financeiro-paola.test`:

   ```bash
   npm install
   npm run build
   ```

Durante o desenvolvimento, `npm run dev` recompila automaticamente.

## Levar o banco para outro servidor (phpMyAdmin)

Não use o "exportar dados" do HeidiSQL/phpMyAdmin com só os `INSERT`: sem a estrutura das tabelas a
importação falha ("Table doesn't exist") e, se as tabelas já existirem, dá "Duplicate entry".
Gere o arquivo pelo sistema:

```bash
php artisan financeiro:exportar-sql
```

O arquivo vai para `database/exportacoes/` (fora do git) e:

- cria as tabelas que faltam (estrutura compatível com MySQL 5.7+/8 e MariaDB);
- inclui usuários, categorias, taxas, pacientes/fornecedores, lançamentos, auditoria e migrations;
- não leva sessões de login nem cache;
- pode ser importado mais de uma vez, ou depois de um `php artisan migrate --seed`, sem duplicar nada.

No phpMyAdmin: selecione o banco → **Importar** → escolha o arquivo → **Importar**.
No servidor, use no `.env` a **mesma `APP_KEY`** do computador de origem (a verificação em duas etapas
fica criptografada com ela).

## Publicar em hospedagem compartilhada sem terminal (ex.: Hostinger)

1. No computador: `composer install --no-dev --optimize-autoloader` e `npm run build`
   (depois do envio, rode `composer install` de novo para voltar a ter as ferramentas de teste).
2. Envie o projeto inteiro para `public_html`, **incluindo** `vendor/`, `public/build/` e os arquivos
   ocultos `.htaccess` (o da raiz e o de `public/`). No Gerenciador de Arquivos, ative "mostrar ocultos".
   O `.htaccess` da raiz encaminha tudo para `public/` e bloqueia o acesso a `.env`, `vendor`, `storage` etc.
3. Crie o `.env` no servidor a partir do `.env.example`: `APP_ENV=production`, `APP_DEBUG=false`,
   `APP_URL=https://seu-dominio`, os dados do banco da hospedagem, `SESSION_SECURE_COOKIE=true`
   e a **mesma `APP_KEY`** do computador de origem.
4. No painel da hospedagem: PHP 8.3 ou superior com a extensão `bcmath`, SSL ativo e "Forçar HTTPS".
5. Importe o banco pelo phpMyAdmin (seção acima).

Se aparecer **403**, quase sempre é o `.htaccess` da raiz que não foi enviado. Se aparecer **500**, veja
`storage/logs/laravel.log` (as pastas `storage/` e `bootstrap/cache/` precisam permitir escrita).

## Testes

```bash
php artisan test
```

Os testes usam SQLite em memória e não tocam no banco real.

## Segurança

O que já está implementado:

- **Autenticação**: sem cadastro público; senhas com bcrypt; política de senha forte (10+ caracteres,
  maiúsculas, minúsculas, números e símbolos; em produção também recusa senhas vazadas).
- **Força bruta**: 5 tentativas por minuto por e-mail+IP e 20 por IP; bloqueios ficam na auditoria.
- **Enumeração de usuários**: mesma mensagem e mesmo tempo de resposta para e-mail inexistente ou senha errada.
- **Verificação em duas etapas (TOTP)** com códigos de recuperação; gerenciá-la exige reconfirmar a senha.
- **Sessões**: no banco, criptografadas, expiram com 60 min sem uso; ID regenerado no login; trocar
  senha, perfil ou desativar um usuário encerra as sessões dele.
- **Autorização** por perfil em todas as rotas (Gates + middleware), testada automaticamente.
- **CSRF** em todos os formulários; **XSS** bloqueado pelo escape do Blade e por uma
  **Content-Security-Policy com nonce** (nenhum script ou estilo inline é permitido).
- **SQL injection**: apenas Eloquent/Query Builder com parâmetros; filtros validados.
- **Cabeçalhos**: X-Frame-Options, nosniff, Referrer-Policy, Permissions-Policy, HSTS em HTTPS e
  `Cache-Control: no-store` nas páginas autenticadas.
- **Valores monetários** em `DECIMAL` e cálculos com `bcmath` (sem erro de arredondamento de float);
  os cálculos enviados pelo navegador são ignorados e refeitos no servidor.
- **Auditoria**: criação, alteração (antes/depois), exclusão, restauração, logins, falhas e 2FA,
  com usuário, IP e navegador. Senhas e segredos nunca são gravados no log.
- **Exclusão lógica** de lançamentos e cadastros (recuperável); categorias em uso não podem ser excluídas.
- **CSV seguro**: textos iniciados por `= + - @` são neutralizados (proteção contra *formula injection*).
- A importação da planilha é feita só pelo terminal, nunca por upload na web.

### Checklist para colocar em produção

- [ ] `APP_ENV=production` e `APP_DEBUG=false`
- [ ] HTTPS obrigatório e `SESSION_SECURE_COOKIE=true`
- [ ] O servidor web deve apontar para a pasta `public/` (nunca para a raiz do projeto)
- [ ] Usuário do MySQL exclusivo, com senha forte e acesso apenas a este banco
- [ ] `php artisan config:cache && php artisan route:cache && php artisan view:cache`
- [ ] Backup diário do banco (ex.: `mysqldump`) guardado fora do servidor e testado
- [ ] Ativar a verificação em duas etapas para todos os usuários, principalmente administradores
- [ ] Rodar `composer audit` e `npm audit` periodicamente e manter dependências atualizadas
- [ ] Remover do `.env` as variáveis `DEMO_ADMIN_*` usadas apenas em desenvolvimento

### LGPD

O sistema guarda nome, telefone e e-mail de pacientes, que são dados pessoais. Registre apenas o necessário
para o controle financeiro, não anote informações de saúde nas observações, mantenha o acesso restrito
aos perfis adequados e os backups protegidos.

## Estrutura

```
app/
  Console/Commands/     criar-admin e importar-planilha
  Enums/                Tipo, status, forma de pagamento, perfis
  Http/Controllers/     Painel, Resumo, Lançamentos, Contatos, Categorias, Taxas, Usuários, Auditoria
  Http/Middleware/      Cabeçalhos de segurança (CSP) e bloqueio de usuário inativo
  Http/Requests/        Validação de todos os formulários
  Models/               Eloquent + trait Auditavel
  Services/             CalculadoraLancamento, RegistroLancamentos, RelatorioFinanceiro
resources/
  views/                Blade + Bootstrap
  scss/app.scss         Tema visual
  js/                   Formulário de lançamento, gráficos, modais
tests/                  Unitários e de funcionalidade
```
