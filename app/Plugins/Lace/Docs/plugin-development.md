# Lace Plugin — Dashboard de Estratégia de IA (metodologia LACE)

Plugin do Leantime com um **dashboard orientado a decisão** da metodologia
LACE. Cada colmeia é um **objetivo (goal)** do Leantime; a cor vai de
**vermelho (0%)** a **verde (100%)** conforme o `goalProgress` do objetivo.

## Comportamento

- Ao abrir `/lace/show`, o plugin **garante** (idempotente):
  1. um projeto dedicado chamado **`LACE`** (criado sem cliente, visível a todos);
  2. um goal board `LACE - Estratégia de IA` nesse projeto;
  3. os **17 objetivos** (colmeias), cada um começando em 0% (`start=0, current=0, end=100`).
- Layout **paisagem nativa e responsivo** (colapsa em coluna única < 1250px), em camadas:
  1. **Camada de decisão** — anel de maturidade geral (média dos 17, com gradiente)
     + 1 KPI por núcleo (média + barra) + meta ("17 objetivos… última atualização em X",
     derivada do `modified` real dos goals);
  2. **Faixa de contexto** — chips "Estratégias alinhadas" com `Alinhamento ⇢ / ⇠ Realinhamento`;
  3. **3 painéis de colmeias** — Portfólio | **Estratégia (hero, cabeçalho carmim)** | Modelo,
     com o ciclo do framework como pills nos vãos (`← Prioridades`, `Valor →`,
     `Planejamento →`, `← Execução`);
  4. **Radar de atenção** — os 3 menores escores com CTA "Atualizar progresso →"
     (link para o goal board).
- Núcleos:
  - **Estratégia de IA** (6): Alinhamento, Impulsionadores, Riscos, Visão, Valor, Adoção
  - **Portfólio de IA** (5): Ideação/priorização, Comprar ou construir, Casos de uso, Gestão de valor/custos, Gestão de mudanças
  - **Modelo operacional de IA** (6): Governança, Engenharia, Dados, Letramento, Tecnologia, Organização
- Cor: rampa sequencial `hsl(6→140, 52%, ~49% com queda no meio)` (contraste do texto);
  células **< 25% ganham badge "!"** (encoding redundante além da cor); células têm
  foco de teclado e `prefers-reduced-motion`; dark mode via `prefers-color-scheme`.

## Como atualizar o progresso

O escore de cada colmeia vem do goal correspondente no projeto **LACE**
(Goals → board "LACE - Estratégia de IA"). Ajuste o **valor atual**
(`currentValue`, faixa 0–100) do objetivo no Leantime e o dashboard
acompanha no próximo carregamento (cor, médias, radar).

> Gotcha de teste: para setar valores via `mysql` CLI use
> `--default-character-set=utf8mb4`, senão os títulos acentuados
> (Visão, Adoção, Governança…) não casam no `WHERE`.

## Internacionalização (i18n)

- Strings de UI em `Language/en-US.ini` (inglês, base) e `Language/pt-BR.ini`.
- `Registration::registerLanguageFiles()` só mescla o idioma do usuário quando
  `session('usersettings.language')` existe — mas o middleware `Localization`
  marca `localization.cached` na request pré-login e passa a retornar cedo,
  deixando essa chave nula na sessão. Por isso o `register.php` adiciona um
  listener próprio (prioridade 6) que resolve o idioma como o core
  (`Language::getCurrentLanguage()`: usuário → empresa → default `pt-BR`)
  e mescla o `.ini` correspondente por cima do en-US.
- **Nomes de núcleos/objetivos (`Dashboard::NUCLEI`) são dados de negócio em
  PT** — precisam casar com os goals semeados no banco; não traduzir.

## Estrutura

```
app/Plugins/Lace/
├── composer.json · register.php · bootstrap.php
├── Controllers/Show.php        → /lace/show (dashboard)
├── Services/
│   ├── Lace.php                → PluginInterface (lifecycle install/enable/…)
│   └── Dashboard.php           → garante projeto/board/goals + lê progresso agrupado
├── Templates/show.blade.php    → colmeias hexagonais + gradiente (view: lace.show)
├── Language/en-US.ini
└── Docs/
```

A lista de objetivos e o agrupamento por núcleo ficam em
`Services/Dashboard.php::NUCLEI` — edite ali para incluir/remover colmeias.

## Convenções deste fork (ver histórico)

Views em `Templates/` (namespace = `lace`), referência `lace.show`;
`Services/Lace.php implements PluginInterface`; `composer.json name = leantime/lace`
com `homepage`/`authors`. Rodar o CLI como `www-data`. Após mexer em view paths,
limpar caches (`bootstrap/cache/*.php`, `storage/framework/{composerPaths,viewPaths}.php`).
