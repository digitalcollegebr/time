# QA — Time (Leantime 3.9.5 custom) — homol 2026-07-01T02:44Z

## Cobertura
| # | Módulo | Ação | Resultado |
|---|--------|------|-----------|
| 1 | Tema/Branding | login + app logado | PASS — navy/magenta/Montserrat/logo DC, sem erros console |
| 2 | Dashboard | render widgets | PASS — Minhas Tarefas, Calendário, stats; sem erros |
| 3 | Projetos | listar (/projects/showAll) | PASS — tabela, paginação, i18n pt-BR |
| 4 | Projetos | criar projeto | PASS — criado, entrou no contexto + modal boas-vindas |
| 5 | Tarefas | Kanban render (5 colunas) | PASS — toggles Kanban/Tabela/Lista, Filter, Group By |
| 6 | Tarefas | criar via quick-add inline | PASS — Tarefa #11 criada, sem erros |
| 7 | Tarefas | modal de detalhe (#11) | PASS — abas Detalhes/Arquivos/Horas, campos, editor, painel Organização |
| 8 | Planilhas de Horas | grade semanal (/timesheets/showMy) | PASS — grade dia a dia + Salvar |
| — | Erro 404 | rota inexistente | PASS — página 404 branded (DC, pt-BR) |
| 9 | Usuários | listar (/users/showAll) | PASS — Daniel/Proprietário, colunas, Adicionar Usuário (não disparei convite: SMTP off) |
| 10 | Config. Empresa | detalhes/API/idioma/logo | PASS — pt-BR, upload logo, "Redefinir p/ logotipo do Time" (whitelabel) |
| 11 | Calendário | mês + prazos de tarefas | PASS — FullCalendar, toggles, Export, i18n |
| 12 | Wiki | estado vazio + Criar artigo | PASS (i18n menor: texto de ajuda em inglês) |
| 13 | Ideias | Mural de Ideias | PASS — pt-BR completo |
| 14 | Metas (Goalcanvas) | dashboard + Create New Goal | PASS (i18n menor: onboarding "Goals to keep you focused" em inglês) |
| 15 | Relatórios | sumário/burndown/marcos | PASS — métricas, gráficos, pt-BR |
| 16 | Clientes | listar (Digital College) | PASS — Novo Cliente, pt-BR |
| 17 | Time Bot (IA) | widget sem keys | PASS — oculto (degradação graciosa) |
| 18 | Fonte/cores logado | computed style | PASS — Montserrat + #ab226d + menu #192a3d |
| — | Console/JS | todas as telas | CLEAN — zero erros de console em todas as páginas |

## Achados
- [MENOR/i18n] Wiki: texto de ajuda em inglês ("Our docs allow you to...").
- [MENOR/i18n] Metas: modal de onboarding em inglês ("Goals to keep you focused").
- [OBS] Sem busca global no header (comportamento nativo do Leantime — busca é contextual).
- [OBS] Cache do CSS de tema usa ?v=appVersion; edição de tema sem bump de versão exige hard-reload (não afeta a migração 3.7→3.9).

## Não testado (requer configuração externa, fora do escopo da homol)
- Convite/criação de usuário por e-mail (SMTP não configurado).
- Envio de e-mails / notificações por e-mail.
- Integrações: OIDC/LDAP/Socialite, calendários externos (Google/iCal), S3.
- Upload de arquivos (Sala de Arquivos) e importação CSV.
