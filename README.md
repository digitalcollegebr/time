
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

O builder padrão do Docker Desktop usa o driver `docker`, que **não faz build multi-arch**
(`Multi-platform build is not supported for the docker driver`). Crie uma vez um builder próprio:

```bash
docker buildx create --name time-builder --driver docker-container --bootstrap
```

E use ele no build:

```bash
docker buildx build --builder time-builder --platform linux/amd64,linux/arm64 \
  -t danielmonteirodc/time:<versão> -t danielmonteirodc/time:latest --push .
```

**Convenção de tag:** as tags acompanham a versão do Leantime (`3.9.5`). Mudanças só do fork sobre a
mesma base usam sufixo de revisão — `3.9.5-1`, `3.9.5-2` — para não sugerir um upstream que não existe.

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
| `LEAN_USE_REDIS` | `false` | **`true` para usar Redis** em sessão e cache. O compose já sobe o serviço `time_redis` |
| `LEAN_REDIS_SCHEME` | `tcp` | **Não use `tls` com o Redis da stack.** O default da aplicação é `tls`; o compose força `tcp` |
| `LEAN_REDIS_HOST` | `time_redis` | Host do Redis (nome do serviço na rede interna) |
| `LEAN_REDIS_PORT` | `6379` | Porta do Redis |
| `LEAN_REDIS_PASSWORD` | — | Opcional. Se definida, o `time_redis` passa a **exigir** a senha |
| `LEAN_REDIS_DB` | `0` | Database do Redis |
| `LEAN_REDIS_URL` | — | Só para Redis **externo** (ex: `tcp://host:6379`); sobrepõe host/porta/senha |
| `LEAN_LOG_CHANNELS` | `stderr` | Canais de log. `stderr` faz o log aparecer no painel do Coolify |

#### Redis (sessão e cache)

O `docker-compose.yml` sobe o serviço **`time_redis`** junto da aplicação. Para ativar, basta
`LEAN_USE_REDIS=true` — as demais variáveis já vêm apontadas para o serviço interno.

Com a flag ligada, a aplicação troca `session.driver` e os stores de cache para Redis
(`SessionServiceProvider` / `CacheServiceProvider`). Dois efeitos práticos:

- **Ligar ou desligar a flag invalida as sessões ativas** — todos os usuários logados caem no
  próximo acesso, porque a sessão passa a ser lida de outro lugar. Prefira fazer isso fora do horário.
- O Redis roda com `appendonly` e volume próprio (`redis_data`) justamente porque a sessão mora nele;
  perder o dataset desloga todo mundo. O cache, por si só, é descartável.

> **Não configure `LEAN_REDIS_SCHEME=tls`** para o Redis desta stack. O default da *aplicação* é
> `tls`, mas o Redis interno fala TCP puro — o compose já força `tcp`. Com `tls`, o host viraria
> `tls://time_redis` e a conexão falharia.

Para usar um Redis **externo** (gerenciado pelo Coolify ou de terceiros), defina `LEAN_REDIS_URL`
e ignore o serviço `time_redis`.

#### Login com Google Workspace (SSO)

Permite que qualquer pessoa com e-mail do domínio corporativo entre com a conta Google, ganhando
usuário automaticamente no primeiro acesso — e **ninguém de fora do domínio**.

| Variável | Default | Descrição |
|---|---|---|
| `LEAN_OIDC_ENABLE` | `false` | `true` mostra o botão "Entrar com Google" e habilita as rotas |
| `LEAN_OIDC_PROVIDER_URL` | — | `https://accounts.google.com` (endpoints vêm por discovery) |
| `LEAN_OIDC_CLIENT_ID` | — | Client ID do OAuth client (um por ambiente) |
| `LEAN_OIDC_CLIENT_SECRET` | — | Secret do OAuth client |
| `LEAN_OIDC_CREATE_USER` | `false` | `true` cria o usuário no primeiro login |
| `LEAN_OIDC_DEFAULT_ROLE` | `20` | Papel dos criados automaticamente (20 = editor) |
| `LEAN_OIDC_ALLOWED_EMAIL_DOMAINS` | — | **Domínios autorizados**, separados por vírgula |
| `LEAN_OIDC_REQUIRE_HOSTED_DOMAIN` | `false` | `true` exige a claim `hd` do Google |
| `LEAN_OIDC_HOSTED_DOMAIN` | — | Filtra o seletor de contas do Google (só UX) |
| `LEAN_TWOFA_ENABLED` | `false` | 2FA próprio do Leantime. **Desligado neste fork** — ver abaixo |

**No Google Cloud:** tela de consentimento **Internal**, credencial **OAuth client ID → Web
application**, e o redirect URI registrado exatamente como `https://<host>/oidc/callback` — o código
o deriva de `LEAN_APP_URL` e não é configurável. Use **clients separados** para produção e homologação.

> **`LEAN_OIDC_ALLOWED_EMAIL_DOMAINS` é obrigatória quando `LEAN_OIDC_CREATE_USER=true`.** Vazia, a
> aplicação nega todos os logins de propósito: sem allowlist, o auto-provisionamento criaria conta
> para qualquer conta Google do mundo, inclusive `@gmail.com`.

O domínio é comparado de forma **exata**, nunca por sufixo — `@fakedigitalcollege.com.br` não é
aceito como `@digitalcollege.com.br`. Com `LEAN_OIDC_REQUIRE_HOSTED_DOMAIN=true`, também é exigida a
claim `hd`, que é o que prende o login ao Workspace gerenciado: uma conta Google **de consumidor**
pode ser registrada com endereço do domínio corporativo, mas não carrega `hd`.

**Mantenha o login por senha habilitado durante o rollout.** É o único caminho de volta se o client
OAuth for mal configurado ou o secret expirar. O primeiro login via Google em produção não deve ser
o da conta owner.

Sincronização de papel **não existe**: grupo do Google não define papel no Time. Promover é manual.

**Verificação em duas etapas** fica a cargo do Google Workspace. O 2FA próprio do Leantime vem
**desligado** neste fork (`LEAN_TWOFA_ENABLED=false`), porque cobrar um segundo fator por cima do
SSO é redundante — e quem já o tinha ativo ficaria preso numa verificação que a interface não
oferece mais como desativar.

Com o interruptor desligado: o gate de verificação não dispara, `/twoFA/edit` é bloqueado no
servidor (não só escondido) e a seção some do perfil. **Os valores em `zp_user` são preservados** —
`LEAN_TWOFA_ENABLED=true` restaura o estado anterior de cada usuário, sem ninguém precisar
reconfigurar o aplicativo autenticador.

> Como o formulário de senha segue habilitado como break-glass, e o 2FA do Leantime era o único
> segundo fator desse caminho, use senha forte na conta owner.

#### Chat de suporte com IA (Time Bot)

| Variável | Default | Descrição |
|---|---|---|
| `LEAN_OPENAI_API_KEY` | — | Chave da OpenAI usada pelo proxy do chat. **Fica só no servidor** — nunca chega ao browser. |
| `LEAN_SUPPORTCHAT_ASSISTANT_ID` | — | ID do assistant treinado na OpenAI (ex: `asst_...`) |
| `LEAN_SUPPORTCHAT_ENABLED` | `true` | Liga/desliga o widget |
| `LEAN_SUPPORTCHAT_SCREEN_CONTEXT` | `true` | Envia o conteúdo da tela atual ao assistant para respostas em contexto |
| `LEAN_SUPPORTCHAT_MODEL` | `gpt-5-nano` | Modelo usado no run. **Sobrepõe o modelo do assistant.** Deixe vazia para usar o padrão |

> O widget só aparece quando **`LEAN_OPENAI_API_KEY` e `LEAN_SUPPORTCHAT_ASSISTANT_ID` estão definidos**. Veja a seção [Chat de suporte com IA (Time Bot)](#chat-de-suporte-com-ia-time-bot-1) para detalhes.

Para a lista completa de variáveis disponíveis, consulte `config/sample.env`.

### 3. Volumes persistentes

O `docker-compose.yml` define os seguintes volumes que **não são removidos em redeploys**:

| Volume | Caminho no container | Conteúdo |
|---|---|---|
| `db_data` | `/var/lib/mysql` | Dados do MySQL |
| `redis_data` | `/data` | Persistência do Redis (**contém as sessões** quando `LEAN_USE_REDIS=true`) |
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
- O **modelo é definido no run** (padrão `gpt-5-nano`), sobrepondo o do assistant. O tier nano é
  proposital: o contexto da tela vai em toda execução e a thread acumula histórico, então o custo é
  dominado por tokens de entrada. Para trocar, use `LEAN_SUPPORTCHAT_MODEL`.

> ⚠️ **A Assistants API v2 será desligada em 26 de agosto de 2026.** O widget depende dela
> (`/threads`, `/runs`, header `OpenAI-Beta: assistants=v2`) e vai parar de funcionar nessa data.
> A migração é para a Responses API (threads → conversations, runs → responses).
> Ver [guia de migração](https://developers.openai.com/api/docs/assistants/migration).
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
