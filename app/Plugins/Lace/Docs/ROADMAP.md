# Lace — Roadmap Técnico

> Planejamento de evolução do plugin Lace (dashboard da metodologia LACE),
> seguindo as práticas do Leantime (ver `CLAUDE.md` na raiz do repo e
> `app/Plugins/Lace/CLAUDE.md`) e o fluxo de trabalho com Claude Code.
> Estimativas assumem dev solo assistido por Claude Code.

## Visão

Evoluir o Lace de *dashboard de opinião* para o **sistema operacional da
consultoria LACE**: escores baseados em evidência, histórico e tendências,
multi-cliente com portfólio comparativo, relatório executivo em 1 clique e
copiloto de IA — cada fase entregando valor de forma independente.

**Princípio arquitetural:** o `currentValue` dos goals do Leantime permanece a
**única fonte de verdade** dos escores. Tudo que o plugin adiciona (snapshots,
rubrica, integrações) lê ou escreve através dos services do core
(`Goalcanvas`), nunca por SQL direto em tabelas do core.

---

## Fases e épicos

### Fase 1 — Fundação da narrativa (E1 → E2 → E3)

---

#### E1 · Snapshots + tendências — `feature/lace-snapshots` (1–2 dias)

**Valor:** sem série temporal não há narrativa de progresso. Destrava "▲ +4pp
em 30 dias" no dashboard (design já aprovado) e é pré-requisito de E2 e E8.

**Escopo (user stories)**
- Como consultor, vejo a variação (▲/▼ pp) de cada colmeia, núcleo e da
  maturidade geral em uma janela (30 dias por padrão).
- Snapshots diários são capturados automaticamente; se o cron não rodou,
  a abertura do dashboard captura o snapshot do dia (fallback lazy).

**Design técnico**
- **Tabela própria do plugin** (prefixo `zp_lace_`), criada em
  `Services/Lace::install()` com `Illuminate\Support\Facades\Schema`
  (mesmo padrão de `Install/Services/SchemaBuilder.php`); dropada em
  `uninstall()`:
  ```
  zp_lace_snapshots
    id INT PK AI · canvasId INT · itemId INT · title VARCHAR(255)
    score TINYINT UNSIGNED · capturedAt DATE
    UNIQUE (itemId, capturedAt) · INDEX (canvasId, capturedAt)
  ```
- **`Repositories/Snapshots.php`** — acesso a dados via Query Builder
  (padrão Repository do core; sem SQL cru).
- **`Services/Snapshots.php`** — `captureBoard(int $canvasId)` (idempotente
  por dia), `getTrends(int $canvasId, int $days = 30): array`.
- **Cron diário** em `register.php`, padrão real do repo
  (`app/Domain/Reports/register.php`):
  `EventDispatcher::add_event_listener('leantime.core.console.consolekernel.schedule.cron', … $scheduler->call(...)->daily())`.
- `Dashboard::getDashboardData()` passa a devolver `delta` por goal/núcleo/geral;
  template exibe `▲/▼ pp` (verde/vermelho, `tabular-nums`), ocultando quando
  não há snapshot antigo.
- Datas sempre com `dtHelper()`/`CarbonImmutable` (regra do repo); erros via
  `Log::error()` (nunca `error_log`).

**Testes/DoD:** unit (delta com/sem histórico, idempotência do capture);
`make test-code-style` + `make phpstan`; verificação visual (screenshot);
i18n en-US + pt-BR das novas strings.

---

#### E2 · Relatório executivo — `feature/lace-report` (1–2 dias)

**Valor:** transforma o dashboard no entregável da consultoria; elimina horas
de PowerPoint.

**Escopo**
- Botão "Relatório" no dashboard → `/lace/report` (visão print-friendly:
  capa com cliente/data, colmeias, KPIs, tendências, radar de atenção,
  campo de recomendações) → imprimir/salvar PDF pelo navegador.
- Export CSV dos escores (reuso do padrão `Goalcanvas/Controllers/Export.php`:
  `Response` com headers de download).

**Design técnico**
- **`Controllers/Report.php`** (`get()`, padrão moderno) reusando
  `Services/Dashboard` + `Services/Snapshots` — controller não chama
  repositório (layer enforcement).
- **`Templates/report.blade.php`** com layout `blank` (sem chrome do app) +
  CSS `@media print`; gradientes/hexágonos com `print-color-adjust: exact`.
- Autorização: mesma do dashboard (projeto LACE acessível ao usuário).

**Testes/DoD:** rota 200 autenticada, print CSS validado por screenshot,
CSV abre no Excel/Sheets (UTF-8 BOM), Pint/PHPStan.

---

#### E3 · Multi-cliente + portfólio — `feature/lace-multiclient` (2–3 dias)

**Valor:** a consultoria atende N clientes; o portfólio comparativo é
ferramenta comercial e de gestão.

**Escopo**
- "Ativar LACE" por projeto (deixa de existir o projeto "LACE" hardcoded;
  ele vira apenas o default/demo).
- `/lace/show/{projectId}` renderiza o dashboard do projeto; seletor de
  projeto no topo.
- `/lace/portfolio` — visão comparativa: uma linha por cliente/projeto com
  maturidade geral, médias por núcleo, tendência e link.

**Design técnico**
- Flag por projeto via **Setting service** do core:
  `projectsettings.{id}.lace.enabled` (sem tabela nova).
- `Dashboard::getDashboardData(int $projectId)` parametrizado; board é
  resolvido/criado por projeto (`ensureBoard($projectId)`).
- **Autorização fail-closed** (regra crítica do repo para canvas — tabelas
  compartilhadas com id único): resolver o projeto real do board e checar
  `GoalcanvasPermissions::VIEW` nesse projeto; **nunca** cair para
  `session('currentProject')`. Portfólio lista apenas projetos de
  `Projects::getProjectsUserHasAccessTo()`.
- Rotas via frontcontroller (`/lace/show/{id}` → `Show::get($params)`);
  HTMX para trocar de projeto sem full reload
  (`Hxcontrollers/Board.php`, partial em `Templates/partials/`).
- Métodos de leitura do service anotados com `@api` (viram JSON-RPC
  `leantime.rpc.…` de graça — integrações futuras).

**Testes/DoD:** acceptance (usuário sem acesso ao projeto → 403/redirect;
com acesso → 200); unit do resolver de board; screenshots do portfólio.

---

### Fase 2 — Rigor metodológico (E4 → E5 → E9)

---

#### E4 · Assessment por rubrica — `feature/lace-rubric` (4–6 dias)

**Valor:** escore calculado a partir de critérios ponderados com evidência —
o que separa "achômetro" de metodologia defensável perante o cliente.

**Escopo**
- Cada colmeia tem uma rubrica (critérios com peso e descritores de nível
  0/25/50/75/100). Rubrica-padrão LACE seeded no `install()`.
- Clicar na colmeia abre modal HTMX de assessment: níveis por critério +
  campo de evidência; salvar recalcula o escore e grava no goal.
- Trilha de auditoria: quem respondeu, quando, com que evidência.

**Design técnico**
- Tabelas do plugin (criadas no `install()`):
  ```
  zp_lace_criteria   id · objectiveTitle · position · weight · levelsJson · active
  zp_lace_responses  id · itemId(goal) · criterionId · level TINYINT ·
                     evidence TEXT · updatedBy INT · updatedAt DATETIME
                     UNIQUE (itemId, criterionId)
  ```
- **`Hxcontrollers/Assessment.php`** (padrão do repo: DI via `init()`,
  `$view` → partial Blade em `Templates/partials/assessment.blade.php`).
- Eventos de cliente no padrão do repo: enum
  `Htmx/HtmxLaceEvents.php` com `lt:lace:assessment.updated`
  (`InteractsWithHtmxEvents`); o dashboard escuta e re-renderiza a colmeia;
  notificação via `$tpl->setNotification()` (emite `lt:ui:notify`).
- Escore = média ponderada → `Goalcanvas::patchGoalItem()` (fonte de verdade
  preservada; dispara os filtros/eventos do core normalmente).
- Services validam entrada e lançam exceções (regra do repo); strict types.

**Testes/DoD:** unit do cálculo ponderado (casos de borda: sem resposta,
peso zero); acceptance do fluxo modal; Pint/PHPStan; i18n.

---

#### E5 · Vínculo estratégia ↔ execução — `feature/lace-execution-link` (1–2 dias)

**Valor:** o % sobe porque o trabalho aconteceu — o pitch do "E" do LACE.

**Escopo & design**
- Usa capacidade **nativa** do Goalcanvas: goal da colmeia com
  `setting = 'linkAndReport'` agrega filhos
  (`getChildGoalsForReporting()`), e goals podem ancorar em milestones
  (`milestoneId`). Zero tabela nova.
- Plugin adiciona: indicador visual na colmeia ("ligada à execução"),
  atalho no modal para vincular goals-filho/milestones do projeto do
  cliente, e no relatório a quebra "estratégico vs execução".
- Cuidado com roll-up cross-project: a autorização segue o padrão do core
  (gate no goal-pai consultado).

**Testes/DoD:** unit do agregado exibido; screenshot do indicador.

---

#### E9 · Governança & compliance vivo — `feature/lace-governance` (1–2 dias, pós-E4)

**Valor:** LGPD/regulação de IA como checklist auditável — obrigatório em
saúde (Coaph) e diferencial de proposta.

**Escopo & design**
- Reusa a infra da rubrica (E4): critérios tipo *checklist* (feito/não feito
  com evidência) anexados à colmeia Governança; template LGPD/gov de IA
  seeded no `install()`.
- Relatório (E2) ganha anexo "Conformidade" listando itens e evidências.

---

### Fase 3 — IA e escala (E7 → E6 → E8)

---

#### E7 · Copiloto LACE — `feature/lace-copilot` (3–5 dias)

**Valor:** uma consultoria de IA que demonstra IA no próprio produto.

**Escopo**
- Botão "Gerar resumo executivo" por núcleo/dashboard (ação explícita do
  usuário — controle de custo); resumo citando tendências e destaques.
- Radar de atenção → "Sugerir plano de ação": recomendações a partir de um
  playbook LACE curado (YAML no plugin) + contexto dos escores.

**Design técnico**
- **`Services/Copilot.php`** reusando a config OpenAI já existente no fork
  (`LEAN_OPENAI_API_KEY` no Environment — mesmo mecanismo do support chat);
  HTTP via client do framework; **somente server-side**.
- Privacidade/guardrails: prompt recebe apenas títulos/escores/tendências —
  nunca dados do cliente além disso; feature-flag
  `LEAN_LACE_COPILOT_ENABLED` (exposta no `sample.env`, lida via
  Environment — padrão de config do repo).
- Cache Laravel dos resumos (invalidado por snapshot novo/assessment);
  respeita Redis quando configurado (regra do repo).
- HTMX: `Hxcontrollers/Copilot.php` + partial com estado de loading
  (`<x-global::loadingText>`); erros → `Log::error()` + notificação amigável.

**Testes/DoD:** unit com client HTTP fake (nunca chamar API em teste);
flag desligada → recurso invisível; Pint/PHPStan.

---

#### E6 · Letramento ← LMS Digital College — `feature/lace-letramento` (spike 1 dia + 2–4 dias)

**Valor:** colmeia Letramento calculada por conclusão real de trilhas/quizzes —
diferencial único (consultoria com escola acoplada).

**Plano**
- **Spike primeiro** (API do LMS: auth, endpoint de conclusão por turma/CPF?)
  → decisão de contrato de integração antes de codar.
- **`Services/Contracts/LiteracyProvider.php`** (interface) +
  `Services/Providers/DigitalCollegeLms.php`; cron diário (mesmo evento de
  scheduler do E1) grava o % no goal Letramento via `patchGoalItem()`.
- Credenciais via env (`LEAN_LACE_LMS_*` no `sample.env`); nunca em código.

---

#### E8 · Benchmark anonimizado — `feature/lace-benchmark` (2–3 dias, requer E3 + ≥5 clientes)

**Valor:** "você está no percentil 30 em Governança" — ouro comercial e
efeito de rede.

**Design técnico**
- Agregação sobre `zp_lace_snapshots` cruzada com os boards LACE (E3);
  percentil por colmeia/núcleo calculado on-the-fly (cachear 1 dia).
- **Anonimização por design:** agregados nunca expõem nomes de
  clientes/projetos; visível só para Admin/Owner; mínimo de N≥5 projetos
  para exibir (k-anonimato simples).
- Exibição: chip "P30" ao lado do escore + seção no relatório.

---

## Processo de trabalho (Leantime + Claude Code)

**Git (dev solo):**
1. Merge de `feature/plugin-lace` → `master` (fecha o ciclo atual).
2. **1 branch por épico** a partir do master (`feature/lace-snapshots`, …);
   commits pequenos com mensagem descritiva; push ao concluir o épico;
   merge no master quando validado no ambiente dev.

**Definition of Done (todo épico):**
- [ ] `make test-code-style` (Pint) e `make phpstan` limpos
- [ ] Unit tests para lógica de cálculo/decisão (Codeception)
- [ ] Verificação end-to-end no app real (rota autenticada + screenshot via
      Selenium do stack `.dev` — nunca confiar só no lint)
- [ ] i18n: strings novas em `en-US.ini` **e** `pt-BR.ini`
- [ ] phpDoc em métodos novos; `@api` nos métodos de service expostos
- [ ] Docs do plugin atualizados; commit + push

**Regras do repo que valem em todo épico** (resumo — detalhe em
`app/Plugins/Lace/CLAUDE.md`): controllers só chamam services; services
validam e chamam repositórios; strict types; `dtHelper()`/CarbonImmutable;
`Log::` facade; HTMX para atualização assíncrona (nunca jQuery AJAX novo);
Blade para views novas; eventos de cliente `lt:lace:*` via enum; autorização
fail-closed em tudo que toca canvas; tabelas do plugin com prefixo
`zp_lace_` criadas no `install()`.

**Claude Code (operacional):** CLI do Leantime sempre como `www-data`;
limpeza de caches após mexer em views/config; `mysql` de teste com
`--default-character-set=utf8mb4`; mockups de UI validados como Artifact
antes de portar para Blade; memória de sessão mantém os gotchas do projeto.

## Riscos e mitigação

| Risco | Mitigação |
|---|---|
| Upstream sync do fork conflitar com o plugin | Todo código em `app/Plugins/Lace/` + `tools/`; zero patch no core |
| Cron não configurado no ambiente do cliente | Fallback lazy de snapshot no load do dashboard (E1) |
| Custo/latência do copiloto | Ação explícita do usuário + cache + feature-flag |
| API do LMS instável/indefinida | Spike antes do épico; interface `LiteracyProvider` isola o acoplamento |
| Benchmark expor cliente | k-anonimato (N≥5), sem nomes, restrito a Admin/Owner |

## Sequência recomendada

```
Fase 1: E1 (1-2d) → E2 (1-2d) → E3 (2-3d)        ~1 semana
Fase 2: E4 (4-6d) → E5 (1-2d) → E9 (1-2d)        ~1,5 semana
Fase 3: E7 (3-5d) → E6 (spike+2-4d) → E8 (2-3d)  ~2 semanas
```
