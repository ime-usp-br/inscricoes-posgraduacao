# Sistema de Inscrições em Pós-Graduação — IME-USP

**Versão:** 0.2.0  
**Instituto de Matemática e Estatística — USP**

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

## 1. Introdução

Sistema web do IME-USP para **inscrição em disciplinas de pós-graduação**: candidatos preenchem o formulário público, pagam via Pix e acompanham o fluxo; secretaria e professores analisam e aprovam inscrições. Integra **Senha Única**, **Replicado** e **WSPIX**.

## 2. Público-alvo

*   **Candidatos** (alunos USP e externos) — inscrição em períodos abertos.
*   **Secretaria** — períodos, disciplinas e aprovação de inscrições.
*   **Professores** — avaliação das inscrições de suas disciplinas.
*   **Equipe de TI** — instalação, manutenção e administração (`Admin`).

## 3. Principais funcionalidades

*   **Inscrição pública** — formulário em etapas, upload de documentos e pagamento Pix unificado.
*   **Secretaria** — cadastro de períodos e disciplinas (com importação do Replicado), listagem, filtros e aprovação (individual ou em bloco).
*   **Professor** — listagem e aprovação/reprovação de inscrições elegíveis.
*   **Autenticação** — Senha Única USP e login local; papéis `Admin`, `Secretario` e `Professor` (docentes na tabela `DOCENTE` do Replicado recebem `Professor` no login).
*   **Integrações USP** — Replicado (disciplinas, validação NUSP/e-mail, docentes) e WSPIX (Pix e webhook de confirmação).
*   **Administração** — painel Filament (`/admin`) para usuários e logs de e-mail.

## 4. Stack Tecnológica

*   **Framework:** Laravel 12
*   **Linguagem:** PHP >= 8.2
*   **Frontend (Stack TALL via Laravel Breeze):**
    *   **Livewire 3 (Class API)**
    *   **Alpine.js 3**
    *   **Tailwind CSS 4** (com suporte a Dark Mode)
    *   **Vite**
*   **Banco de Dados:** Suporte padrão do Laravel (MySQL, MariaDB, PostgreSQL, SQLite)
*   **Integrações USP:**
    *   `uspdev/senhaunica-socialite`
    *   `uspdev/replicado`
*   **Autenticação Scaffolding:** `laravel/breeze`
*   **Permissões:** `spatie/laravel-permission`
*   **Testes:** **PHPUnit**, **Laravel Dusk**
*   **Qualidade:** Laravel Pint, Larastan

## 5. Instalação

Desenvolvimento local com **Docker (Sail)** ou **sem Docker**. Em produção, use `docker-compose.prod.yml`.

### 5.1. Com Docker (Sail) — recomendado

**Pré-requisitos:** Docker, Docker Compose, Git e Composer (no host, na primeira vez).

```bash
git clone <url-do-repositorio> inscricoes-posgraduacao
cd inscricoes-posgraduacao
composer install
cp .env.example .env
```

No `.env`, configure no mínimo:

```dotenv
APP_URL=http://localhost:5004
APP_PORT=5004

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=

WWWUSER=1001
WWWGROUP=1001
```

Configure também Senha Única, Replicado e WSPIX (seção 7).

```bash
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail npm install
./vendor/bin/sail artisan migrate --seed
./vendor/bin/sail npm run dev   # terminal separado
```

O `docker-compose.yml` sobe a aplicação, MySQL, worker de filas e Selenium. A imagem inclui `pdo_sqlsrv` para o Replicado (banco externo — o container precisa alcançá-lo na rede USP/VPN).

**Alias:** `alias sail='./vendor/bin/sail'`

### 5.2. Sem Docker

**Pré-requisitos:** PHP 8.2+ (`pdo_mysql`; para Replicado: `pdo_sqlsrv`), Composer, Node 18+, MySQL 8+.

```bash
git clone <url-do-repositorio> inscricoes-posgraduacao
cd inscricoes-posgraduacao
composer install && npm install
cp .env.example .env
php artisan key:generate
```

No `.env`, use MySQL local (`DB_HOST=127.0.0.1`) e as integrações USP (seção 7).

```bash
php artisan migrate --seed
php artisan serve          # terminal 1
npm run dev                # terminal 2
php artisan queue:work database --queue=high,default,low   # opcional
```

### 5.3. Produção (Docker)

```bash
# .env com APP_ENV=production, APP_DEBUG=false, DB_HOST=mysql, credenciais USP
docker compose -f docker-compose.prod.yml up -d --build
docker compose -f docker-compose.prod.yml exec app php artisan migrate --force
```

Porta padrão: `8016` (`APP_PORT`). Script auxiliar: `scripts/deploy.sh`.

## 6. Uso básico

### 6.1. Rotas principais

| Rota | Acesso |
|------|--------|
| `/` | Público — inscrição |
| `/login` | Senha Única |
| `/login/local` | E-mail e senha |
| `/secretaria` | `Admin`, `Secretario` |
| `/professor` | `Admin`, `Professor` |
| `/admin` | `Admin` (Filament) |

### 6.2. Comandos úteis (Sail)

```bash
sail up -d / sail down
sail artisan migrate
sail npm run dev / sail npm run build
sail artisan test
sail shell / sail logs
```

Sem Docker: substitua `sail` por `php artisan` / `npm` conforme o comando.


## 7. Configurações Específicas da USP

Para que as funcionalidades de integração com a USP funcionem corretamente, você **precisa** configurar as credenciais apropriadas no seu arquivo `.env`.

*   **Senha Única:** Adicione e preencha as variáveis `SENHAUNICA_CALLBACK`, `SENHAUNICA_KEY`, `SENHAUNICA_SECRET`. Consulte a [documentação do `uspdev/senhaunica-socialite`](https://github.com/uspdev/senhaunica-socialite) para detalhes sobre como obter essas credenciais.
*   **Replicado:** Adicione e preencha as variáveis `REPLICADO_HOST`, `REPLICADO_PORT`, `REPLICADO_DATABASE`, `REPLICADO_USERNAME`, `REPLICADO_PASSWORD`, `REPLICADO_CODUND`, `REPLICADO_CODBAS`. Consulte a [documentação do `uspdev/replicado`](https://github.com/uspdev/replicado) para detalhes.

*Instruções detalhadas sobre a configuração e uso dessas integrações podem ser encontradas na [Wiki do Projeto](https://github.com/ime-usp-br/laravel_12_starter_kit/wiki).*

## 8. Ferramentas e Qualidade de Código

Este Starter Kit inclui ferramentas para ajudar a manter a qualidade e a consistência do código:

*   **Laravel Pint:** Formatador de código automático (PSR-12).
    *   Para formatar: `vendor/bin/pint`
    *   Para verificar (CI): `vendor/bin/pint --test`
*   **Larastan (PHPStan):** Ferramenta de análise estática para encontrar erros sem executar o código.
    *   Para analisar: `vendor/bin/phpstan analyse`
*   **EditorConfig:** Arquivo `.editorconfig` na raiz para padronizar configurações básicas do editor (indentação, fim de linha, etc.). Garanta que seu editor tenha o plugin EditorConfig instalado e ativado.

## 9. Testes

*   **Executando Testes PHPUnit (Unitários e Feature):** Use o comando Artisan:
    ```bash
    php artisan test
    ```
*   **Executando Testes Dusk (Browser / End-to-End):** Rodar testes Dusk requer que **o servidor da aplicação e o ChromeDriver estejam rodando simultaneamente** antes de executar o comando de teste.
    1.  **Terminal 1 - Servidor da Aplicação:**
        ```bash
        php artisan serve
        ```
        *(Mantenha este terminal rodando)*
    2.  **Terminal 2 - ChromeDriver:**
        *   **Problema Comum:** Em alguns ambientes, o comando `php artisan dusk:chrome-driver` pode *não* manter o processo rodando como esperado, saindo imediatamente após confirmar a instalação.
        *   **Solução Manual:** Se o comando acima sair imediatamente, inicie o ChromeDriver manualmente, **especificando a porta 9515** (ou a porta definida em `DUSK_DRIVER_URL` no seu `.env.dusk.local`). Encontre o executável correto para seu sistema operacional dentro de `./vendor/laravel/dusk/bin/` e execute-o com a flag `--port`:
          ```bash
          # Exemplo para Linux:
          ./vendor/laravel/dusk/bin/chromedriver-linux --port=9515

          # Exemplo para macOS (Intel):
          # ./vendor/laravel/dusk/bin/chromedriver-mac-x64 --port=9515

          # Exemplo para macOS (Apple Silicon):
          # ./vendor/laravel/dusk/bin/chromedriver-mac-arm64 --port=9515

          # Exemplo para Windows (use Git Bash ou similar):
          # ./vendor/laravel/dusk/bin/chromedriver-win.exe --port=9515
          ```
        *(Mantenha este terminal rodando. Você deve ver uma mensagem como "ChromeDriver was started successfully on port 9515.")*
    3.  **Terminal 3 - Executar Testes Dusk:**
        ```bash
        php artisan dusk
        ```
*   **Fakes para Dependências USP:** O kit inclui classes `Fake` (ex: `FakeReplicadoService`, `FakeSenhaUnicaSocialiteProvider`) para facilitar a escrita de testes que interagem com as funcionalidades da Senha Única ou Replicado sem depender dos serviços reais (Planejado).

## 10. Documentação

A documentação principal e mais detalhada deste Starter Kit reside na **[Wiki do GitHub](https://github.com/ime-usp-br/laravel_12_starter_kit/wiki)**.

Lá você encontrará:

*   Este README.md.
*   O [Guia de Estratégia de Desenvolvimento](./docs/guia_de_desenvolvimento.md) completo (v0.1.0).
*   Os [Padrões de Código e Boas Práticas](./docs/padroes_codigo_boas_praticas.md) (v0.1.0).
*   A [Estratégia de Versionamento da Documentação](./docs/versionamento_documentacao.md) (v0.1.0).
*   O [Termo de Abertura do Projeto](./docs/termo_abertura_projeto.md) (v0.1.0).
*   Registros de Decisão de Arquitetura (ADRs) em `docs/adr/`.
*   Detalhes sobre a arquitetura do código (Services, Repositories).
*   Explicações sobre o sistema de permissões e autenticação.
*   Tutoriais sobre como estender o kit.
*   Guias de configuração avançada e deploy (se aplicável).
*   Como usar os fakes para testes.

## 11. Como Contribuir

Contribuições são bem-vindas! Para garantir um desenvolvimento organizado e rastreável, siga o fluxo descrito no **[Guia de Estratégia de Desenvolvimento](./docs/guia_de_desenvolvimento.md)**.

Em resumo:

1.  Identifique ou crie uma **Issue** atômica no GitHub descrevendo a tarefa (bug, feature, chore).
2.  Crie um **Branch** específico para a Issue a partir do branch principal (`main` ou `develop`).
3.  Faça **Commits Atômicos** e frequentes, sempre referenciando a Issue ID na mensagem (`#<ID>`).
4.  Abra um **Pull Request (PR)** claro, vinculando-o à Issue (`Closes #<ID>`).
5.  Aguarde a revisão (mesmo que seja auto-revisão) e a passagem da CI.
6.  Faça o **Merge** do PR.

## 12. Licença

Este projeto é licenciado sob a **Licença MIT**. Veja o arquivo [LICENSE](./LICENSE) para mais detalhes.
