# Sincronização com o Leantime Upstream

Este repositório é um fork do [Leantime](https://github.com/Leantime/leantime) rebranded como **Time** (Digital College).

## Estrutura de commits

```
... commits do Leantime ... → leantime-base → feat(whitelabel): rebrand
```

Todo o rebrand está em **um único commit** no topo. Isso permite usar `rebase` para incorporar novidades do upstream sem sujar o histórico.

## Quando o Leantime lançar nova versão

### 1. Buscar as novidades

```bash
git fetch upstream
```

### 2. Verificar o que mudou

```bash
# Ver os novos commits desde nossa base
git log leantime-base..upstream/master --oneline

# Ver quais arquivos foram alterados
git diff leantime-base upstream/master --stat
```

### 3. Fazer o rebase

```bash
git rebase upstream/master
```

O Git vai:
1. Remover temporariamente nosso commit de rebrand
2. Aplicar todos os novos commits do Leantime
3. Reaplicar o commit de rebrand por cima

### 4. Resolver conflitos (se houver)

Os arquivos que mais tendem a conflitar são os mesmos que modificamos no rebrand. Para cada conflito:

```bash
# Ver o que conflitou
git status

# Editar o arquivo, escolher o que manter, depois:
git add <arquivo>

# Continuar o rebase
git rebase --continue
```

**Conflitos esperados e como resolver:**

| Arquivo | O que fizemos | O que o Leantime pode mudar |
|---|---|---|
| `app/Language/pt-BR.ini` | Traduções + trocas de nome | Adicionar novas chaves (aceitar as deles + manter as nossas) |
| `app/Core/Configuration/DefaultConfig.php` | `sitename='Time'`, `language='pt-BR'` | Qualquer outra propriedade (manter nossas 2 linhas) |
| Templates Blade | Remoção de links externos, trocas de nome | Novos features nos templates (integrar manualmente) |
| Serviços PHP | Troca de strings "Leantime" → "Time" em emails | Lógica de negócio (aceitar a deles + reaplicar troca de nome) |

### 5. Atualizar a tag base e subir

```bash
# Mover a tag base para o novo topo do upstream
git tag -f leantime-base upstream/master

# Push forçado (rebase reescreve histórico)
git push --force-with-lease origin master
git push --force origin leantime-base
```

### 6. Rebuildar e testar

```bash
docker compose up --build -d
```

Verificar: login, modal de boas-vindas em pt-BR, nome "Time" nas telas.

---

## Arquivos modificados no rebrand

Para referência rápida ao resolver conflitos:

- **Configuração**: `app/Core/Configuration/DefaultConfig.php`
- **Tradução**: `app/Language/pt-BR.ini`
- **Imagens/logos**: `public/assets/images/*`
- **Tema**: `public/theme/default/theme.ini`
- **Templates**: `app/Domain/Auth/`, `app/Domain/Help/`, `app/Domain/Menu/`, `app/Domain/Notifications/`, `app/Domain/Plugins/`, `app/Domain/Widgets/`, `app/Views/`
- **Serviços**: `app/Domain/Auth/Services/Auth.php`, `app/Domain/TwoFA/Services/TwoFA.php`, `app/Domain/Notifications/Services/`
- **Deploy**: `Dockerfile`, `docker-compose.yml`, `.dockerignore`
