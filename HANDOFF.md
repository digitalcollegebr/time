# Estado atual do desenvolvimento

> Retrato em **2026-08-06**. Este documento é um instantâneo e envelhece: confira o git e o Docker
> Hub antes de confiar nos números. A arquitetura estável mora no [CLAUDE.md](CLAUDE.md); o
> procedimento de deploy, no [README.md](README.md); o rebase com o upstream, no
> [UPSTREAM-SYNC.md](UPSTREAM-SYNC.md).

## Onde as coisas estão

| | |
|---|---|
| `origin/master` | `5f6bc5443` |
| Tag base do upstream | `leantime-base` (Leantime 3.9.5) |
| Imagem mais recente | `danielmonteirodc/time:3.9.5-4` = `latest` |
| **Rodando em produção** | **`3.9.5-3`** — o redeploy para `-4` ainda não foi feito |

A `-4` acrescenta **apenas** o botão do Google na tela de login. O SSO e todas as proteções já estão
ativos na `-3`.

Convenção de tag: acompanham a versão do Leantime; mudança só do fork sobre a mesma base ganha
sufixo de revisão (`3.9.5-1`, `-2`, …). Não invente `3.9.6` — isso sugeriria um upstream que não existe.

## O que foi feito (8 commits sobre `b9b851bf2`)

| Commit | O quê |
|---|---|
| `962d88b20` | CLAUDE.md passa a documentar o fork e suas divergências |
| `ac1692558` | Compose de produção corrigido para o proxy do Coolify (`expose`, vars obrigatórias, healthcheck do banco) |
| `0a0cd0cf7` | Serviço `time_redis` na stack |
| `69098e99b` | `gpt-5-nano` como modelo padrão do Time Bot |
| `7e543efa9` | Build multi-arch destravado (phpredis pinado, builder `docker-container`) |
| `98654a12c` | **Login com Google Workspace restrito ao domínio** |
| `d012d560a` | 2FA próprio do Leantime desligado |
| `5f6bc5443` | Botão padronizado do Google |

## Pendências

### Imediatas
1. **Teste de aceitação com Gmail pessoal** — deve ser recusado **e não criar linha** em `zp_user`.
   É o que prova a restrição de domínio. Ainda não executado em produção.
   ```sql
   SELECT id, username, role, source FROM zp_user WHERE source='oidc' ORDER BY id DESC LIMIT 5;
   ```
   Se a tela de consentimento do Google estiver como *Internal*, o Google pode barrar antes do
   callback — também é sucesso, mas de outra camada.
2. **Redeploy para `3.9.5-4`** (só o botão).
3. **Rodar `php bin/leantime cache:clearAll` depois de qualquer redeploy** que mexa em tradução.
   O cache de idioma é `file` e sobrevive a restart de container — foi o que fez o botão parecer
   não ter mudado.
4. **Rotacionar o `LEAN_OIDC_CLIENT_SECRET`.** Ele foi colado num log de conversa durante a
   configuração. O Google aceita dois secrets simultâneos, então dá para trocar sem downtime.

### Com prazo
5. **A Assistants API v2 da OpenAI desliga em 2026-08-26.** O widget Time Bot
   (`app/Domain/Supportchat/`) depende de `/threads`, `/runs` e do header `assistants=v2`, e **para
   de funcionar nessa data**. A migração é para a Responses API (threads → conversations, runs →
   responses). São 3 arquivos, e os invariantes do domínio, listados no CLAUDE.md, dão para preservar.

### Quando der
6. **Reconciliar `zp_user.username`** dos demais usuários. Quem tiver username diferente do e-mail
   Workspace ganha uma **segunda** conta ao entrar pelo Google — não há vínculo por `sub`. O owner
   (`daniel.monteiro@digitalcollege.com.br`) já foi conferido.
7. **Fixação de sessão**: `setUserSession()` não regenera o ID da sessão. Pré-existente para senha e
   OIDC, mas o SSO eleva a aposta.
8. **Submeter ao upstream** as correções de `state`, `aud` e do re-hash de senha — são bugs genéricos
   do Leantime, e mergeá-los lá elimina três pontos de conflito no rebase.
9. **pt-BR trai ~100 linhas do en-US**, e strings sem tradução vazam inglês na interface.

## Decisões tomadas de propósito (não "conserte" sem contexto)

- **Login por senha continua habilitado.** É o break-glass. Como o 2FA do Leantime foi desligado, a
  senha do owner virou o único fator desse caminho — precisa ser forte.
- **`LEAN_USE_REDIS` fica em `false`.** O serviço `time_redis` sobe junto, mas ligar a flag troca o
  armazenamento de sessão e **desloga todos**. Não misture isso com outra mudança no mesmo deploy.
- **Nada foi apagado de `zp_user`** ao desligar o 2FA. `twoFAEnabled` e `twoFASecret` seguem
  gravados, então religar restaura o estado de cada usuário sem reconfigurar autenticador.
- **O botão do Google não usa os tokens do tema.** Cores fixas por diretriz de marca; pintá-lo de
  magenta o descaracterizaria.
- **Compressão LZ4 do Redis é ignorada silenciosamente** — a extensão phpredis é compilada sem LZ4 e
  `setOption` devolve `false` sem lançar. Verificado; não é bloqueador, e o config é do upstream.

## Ambiente de desenvolvimento

O [CLAUDE.md](CLAUDE.md) traz os comandos completos para rodar Pint, PHPStan e os testes **sem PHP
instalado**, tudo em container, mais os baselines esperados. Dois pontos que economizam tempo:

- O builder multi-arch precisa existir na máquina nova:
  `docker buildx create --name time-builder --driver docker-container --bootstrap`
- `vendor/` não está versionado e não existe numa cópia nova; instale antes de rodar as ferramentas.
