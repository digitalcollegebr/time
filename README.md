
<div align="center">

# Time — Digital College

Sistema de gerenciamento de projetos interno da Digital College.

**Fork do [Leantime](https://github.com/Leantime/leantime) v3.6.x com whitelabel e customizações próprias.**

</div>

---

## O que é este repositório

Fork do Leantime OSS rebranded como **Time** para uso interno da Digital College. O produto upstream continua sendo desenvolvido pela equipe do Leantime; nós incorporamos as novidades periodicamente via rebase (ver [Sincronização com upstream](#-sincronização-com-upstream)).

**Customizações em relação ao upstream:**

| O que | Onde |
|---|---|
| Nome, logos e identidade visual (`Time` / Digital College) | `public/assets/images/`, `public/theme/` |
| Idioma padrão `pt-BR` | `app/Core/Configuration/DefaultConfig.php` |
| Traduções pt-BR | `app/Language/pt-BR.ini` |
| Remoção de links externos (suporte, marketplace Leantime) | Templates em `app/Domain/*/` e `app/Views/` |
| Chat de suporte com IA (**Time Bot**, OpenAI Assistants) que lê o contexto da tela | `app/Domain/Supportchat/` |
| Dockerfile multi-stage de produção | `Dockerfile` |
| `docker-compose.yml` para deploy via Coolify | `docker-compose.yml` |
| `LEAN_SESSION_PASSWORD` obrigatório sem default | `app/Core/Configuration/laravelConfig.php` |

---

## Requisitos do sistema

- PHP 8.3+
- MySQL 8.0+ ou MariaDB 10.6+
- Extensões PHP: BC Math, Ctype, cURL, DOM, Exif, Fileinfo, Filter, GD, Hash, LDAP, Multibyte String, MySQL, OPcache, OpenSSL, PCNTL, PCRE, PDO, Phar, Session, Tokenizer, Zip, SimpleXML

---

## Ambiente de desenvolvimento

O método recomendado é via Docker.

**Pré-requisitos:** `docker`, `docker compose`, `make`, `composer`, `git`, `npm`

```bash
# 1. Primeira vez: limpa, instala dependências e builda a imagem dev
make clean build

# 2. Sobe o servidor de desenvolvimento
make run-dev
```

Serviços disponíveis após o start:

| Serviço | URL | Credenciais / Observação |
|---|---|---|
| Aplicação | http://localhost:8090 | Acesse `/install` na primeira vez |
| MailDev | http://localhost:8081 | Captura todos os e-mails enviados |
| phpMyAdmin | http://localhost:8082 | Login: `leantime` / `leantime` |
| S3Ninja | http://localhost:8083 | Habilitar em `.dev/.env` |

> **Atenção:** não altere as credenciais de banco no `.env` de desenvolvimento — isso desconecta a aplicação do container MySQL do Docker.

### Comandos de build

```bash
make build-dev      # build com source maps (desenvolvimento)
make build          # build de produção
npx mix             # build direto via webpack
make clear-cache    # limpa o cache da aplicação
```

---

## Testes

```bash
make phpstan             # análise estática (nível 0)
make test-code-style     # verifica estilo com Laravel Pint
make fix-code-style      # corrige estilo automaticamente
make unit-test           # testes unitários (requer Docker)
make acceptance-test     # testes de aceitação (requer Docker)
```

**Grupos específicos de acceptance tests:**

```bash
# API
docker compose --file .dev/docker-compose.yaml --file .dev/docker-compose.tests.yaml \
  exec leantime-dev php vendor/bin/codecept run -g api --steps

# Timesheets
docker compose --file .dev/docker-compose.yaml --file .dev/docker-compose.tests.yaml \
  exec leantime-dev php vendor/bin/codecept run -g timesheet --steps
```

Grupos disponíveis: `api`, `timesheet`, `login`, `ticket`, `user`

---

## Deploy em produção (Coolify)

O deploy usa o `docker-compose.yml` deste repositório, que **puxa a imagem pronta** `danielmonteirodc/time` do Docker Hub. O **build sempre acontece na máquina do dev** (nunca no servidor) e a imagem é publicada multi-arch (amd64 + arm64):

```bash
docker buildx build --platform linux/amd64,linux/arm64 \
  -t danielmonteirodc/time:<versão> -t danielmonteirodc/time:latest --push .
```

Depois, ajuste `TIME_IMAGE_TAG` (ou o default no compose) e faça o redeploy. Para buildar do código-fonte localmente, troque `image:` pelo bloco `build:` comentado no topo do `docker-compose.yml`.

A imagem usa nginx + php-fpm gerenciados pelo supervisord, exposta na porta `8080`.

### Painel Coolify

Acesse o Coolify da Digital College em **https://app.digitalgenai.com.br/**

### 1. Configurar o projeto no Coolify

1. Acesse **https://app.digitalgenai.com.br/** e entre no projeto correspondente
2. Crie um novo resource: **New Resource → Docker Compose**
3. Aponte para este repositório (ou cole o conteúdo de `docker-compose.yml`)
4. Defina as variáveis de ambiente na aba **Environment Variables** (ver seção abaixo)
5. No serviço **`time`**, configure o domínio **com a porta interna**: `https://time.digitalcollege.com.br:8080`.
   O compose não publica porta no host (usa `expose`) — quem termina TLS e roteia é o proxy do Coolify.
   A porta no domínio diz ao proxy para onde encaminhar; ela **não** aparece na URL pública.
6. Salve e faça o deploy

> `LEAN_APP_URL` precisa bater exatamente com o domínio configurado no passo 5 (sem a porta),
> senão links absolutos, e-mails e redirects saem com o host errado.
>
> As variáveis obrigatórias usam a sintaxe `${VAR:?}`: se faltar alguma, o deploy **falha na hora**
> com a mensagem do que falta, em vez de subir a aplicação com senha vazia.

### 2. Variáveis de ambiente

#### Obrigatórias — **devem** ser definidas no Coolify

| Variável | Descrição | Como gerar |
|---|---|---|
| `LEAN_DB_PASSWORD` | Senha do usuário do banco | `openssl rand -base64 32` |
| `MYSQL_ROOT_PASSWORD` | Senha root do MySQL | `openssl rand -base64 32` |
| `LEAN_APP_URL` | URL pública completa da aplicação | Ex: `https://time.digitalcollege.com.br` |
| `LEAN_SESSION_PASSWORD` | Chave de criptografia de sessão | `openssl rand -base64 32` |

> A aplicação **lança exceção** na inicialização se `LEAN_SESSION_PASSWORD` não estiver definida.

#### Opcionais (com defaults razoáveis)

| Variável | Default | Descrição |
|---|---|---|
| `LEAN_DB_DATABASE` | `leantime` | Nome do banco de dados |
| `LEAN_DB_USER` | `leantime` | Usuário do banco |
| `LEAN_SITENAME` | `Time` | Nome exibido na interface |
| `TIME_IMAGE_TAG` | `3.9.5` | Tag da imagem `danielmonteirodc/time` a ser puxada |
| `LEAN_DEFAULT_TIMEZONE` | `America/Fortaleza` | Timezone da aplicação e do container |
| `LEAN_DEBUG` | `0` | Debug — manter `0` em produção |
| `LEAN_EMAIL_RETURN` | — | E-mail remetente (**sem isto a aplicação não envia e-mail**) |
| `LEAN_EMAIL_USE_SMTP` | `false` | Usar SMTP externo |
| `LEAN_EMAIL_SMTP_HOSTS` | — | Host SMTP |
| `LEAN_EMAIL_SMTP_PORT` | — | Porta SMTP |
| `LEAN_EMAIL_SMTP_USERNAME` | — | Usuário SMTP |
| `LEAN_EMAIL_SMTP_PASSWORD` | — | Senha SMTP |
| `LEAN_EMAIL_SMTP_SECURE` | — | Protocolo (`TLS`, `SSL`, `STARTTLS`) |
| `LEAN_EMAIL_SMTP_AUTH` | `true` | SMTP exige autenticação |
| `LEAN_USE_S3` | `false` | Usar S3 para upload de arquivos |
| `LEAN_S3_KEY` | — | Chave S3 |
| `LEAN_S3_SECRET` | — | Secret S3 |
| `LEAN_S3_BUCKET` | — | Bucket S3 |
| `LEAN_S3_REGION` | — | Região S3 |
| `LEAN_USE_REDIS` | `false` | Usar Redis para sessão/cache (Redis **externo** — esta stack não sobe um) |
| `LEAN_REDIS_URL` | — | URL Redis (ex: `tcp://host:6379`) |
| `LEAN_LOG_CHANNELS` | `stderr` | Canais de log. `stderr` faz o log aparecer no painel do Coolify |

#### Chat de suporte com IA (Time Bot)

| Variável | Default | Descrição |
|---|---|---|
| `LEAN_OPENAI_API_KEY` | — | Chave da OpenAI usada pelo proxy do chat. **Fica só no servidor** — nunca chega ao browser. |
| `LEAN_SUPPORTCHAT_ASSISTANT_ID` | — | ID do assistant treinado na OpenAI (ex: `asst_...`) |
| `LEAN_SUPPORTCHAT_ENABLED` | `true` | Liga/desliga o widget |
| `LEAN_SUPPORTCHAT_SCREEN_CONTEXT` | `true` | Envia o conteúdo da tela atual ao assistant para respostas em contexto |

> O widget só aparece quando **`LEAN_OPENAI_API_KEY` e `LEAN_SUPPORTCHAT_ASSISTANT_ID` estão definidos**. Veja a seção [Chat de suporte com IA (Time Bot)](#chat-de-suporte-com-ia-time-bot-1) para detalhes.

Para a lista completa de variáveis disponíveis, consulte `config/sample.env`.

### 3. Volumes persistentes

O `docker-compose.yml` define os seguintes volumes que **não são removidos em redeploys**:

| Volume | Caminho no container | Conteúdo |
|---|---|---|
| `db_data` | `/var/lib/mysql` | Dados do MySQL |
| `userfiles` | `/var/www/html/userfiles` | Arquivos enviados pelos usuários |
| `public_userfiles` | `/var/www/html/public/userfiles` | Arquivos públicos dos usuários |
| `plugins` | `/var/www/html/app/Plugins` | Plugins instalados |
| `logs` | `/var/www/html/storage/logs` | Logs da aplicação |

> **Backup:** antes de qualquer atualização, faça backup do volume `db_data` e dos volumes de arquivos.

### 4. Primeira instalação

Após o deploy inicial, acesse `<LEAN_APP_URL>/install` para:
- Inicializar o esquema do banco de dados
- Criar a conta de administrador

### 5. Atualização

Faça um novo deploy pelo botão **Redeploy** no painel do Coolify em **https://app.digitalgenai.com.br/**, ou via linha de comando:

```bash
docker compose up --build -d
```

Se houver migrações de banco pendentes, a aplicação redirecionará automaticamente para `<LEAN_APP_URL>/update`.

---

## Chat de suporte com IA (Time Bot)

O **Time Bot** é um assistente de suporte integrado às telas internas (aparece só **após o login**). Ele ajuda os usuários a tirar dúvidas sobre o sistema e sobre projetos de IA Generativa, e **lê o conteúdo da tela atual** para responder em contexto ("em que tela estou?", "o que esse campo faz?", etc.).

### Como funciona

- **Widget nativo** (botão flutuante + painel de chat) com a identidade visual do produto, incluído no layout interno (`app/Views/Templates/layouts/app.blade.php`).
- **Proxy server-side** (`app/Domain/Supportchat/`): o browser conversa apenas com o endpoint interno autenticado `POST /supportchat/message`. A chave da OpenAI **nunca** é exposta ao cliente.
- Usa a **OpenAI Assistants API v2**. O contexto da tela é enviado como `additional_instructions` por execução, preservando as instruções treinadas do assistant.
- A conversa é mantida no `sessionStorage` do browser (thread da OpenAI) — **não há armazenamento em banco**.

### Configuração

1. Crie/treine um assistant na [plataforma da OpenAI](https://platform.openai.com/assistants) e anote o `assistant_id`.
2. Gere uma API key da OpenAI.
3. Defina as variáveis de ambiente (ver tabela [Chat de suporte com IA (Time Bot)](#chat-de-suporte-com-ia-time-bot)):
   - `LEAN_OPENAI_API_KEY`
   - `LEAN_SUPPORTCHAT_ASSISTANT_ID`
4. (Opcional) Ajuste `LEAN_SUPPORTCHAT_ENABLED` e `LEAN_SUPPORTCHAT_SCREEN_CONTEXT`.

O widget só é renderizado quando key + assistant estão configurados; caso contrário, fica oculto sem qualquer impacto na aplicação.

> **Segurança:** trate a `LEAN_OPENAI_API_KEY` como segredo. Defina-a apenas via variável de ambiente (Coolify / `.env` não versionado), nunca no código. Se a chave vazar, **rotacione-a** no painel da OpenAI.

---

## Sincronização com upstream

Quando o Leantime lançar nova versão, consulte o processo completo em **[UPSTREAM-SYNC.md](UPSTREAM-SYNC.md)**.

Resumo do processo:

```bash
# 1. Buscar novidades
git fetch upstream

# 2. Ver o que mudou
git log leantime-base..upstream/master --oneline

# 3. Fazer o rebase (nosso whitelabel sobe por cima)
git rebase upstream/master

# 4. Resolver conflitos, se houver, depois:
git push --force-with-lease origin master

# 5. Atualizar a tag base
git tag -f leantime-base upstream/master
git push --force origin leantime-base
```

Todo o whitelabel está concentrado em **poucos commits no topo do histórico**, tornando o rebase simples na maioria dos casos.

---

## Estrutura do projeto

```
app/
  Core/          # Framework e componentes base (Laravel estendido)
  Domain/        # ~42 módulos de domínio (Tickets, Projects, Users…)
  Views/         # Layouts e componentes Blade compartilhados
  Language/      # Arquivos de tradução (INI)
  Plugins/       # Submodule privado (plugins comerciais)
config/
  sample.env     # Template com todas as variáveis disponíveis
  .env           # Configuração local (não commitar)
public/
  assets/        # CSS, JS, imagens, fontes
  dist/          # Assets compilados (saída do webpack)
  theme/         # Temas (default, minimal)
Dockerfile       # Build multi-stage para produção
docker-compose.yml
UPSTREAM-SYNC.md # Procedimento para atualizar do upstream Leantime
CLAUDE.md        # Documentação técnica detalhada da arquitetura
```

Para documentação detalhada da arquitetura (padrões de código, HTMX, sistema de templates, eventos, etc.), consulte o **[CLAUDE.md](CLAUDE.md)**.

---

## Licença

Leantime é licenciado sob **AGPLv3**.
Plugins no diretório `/app/Plugins` podem estar sob outras licenças (incluindo licença enterprise do Leantime).
