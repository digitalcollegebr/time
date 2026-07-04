# CLAUDE.md — Lace plugin

Scoped guidance for working inside `app/Plugins/Lace/`. The repo-root
`CLAUDE.md` (Leantime architecture) always applies; this file adds the
plugin-specific conventions and hard-won gotchas. The evolution plan lives
in `Docs/ROADMAP.md` — follow its epic branches and Definition of Done.

## What this plugin is

Decision-oriented dashboard for the LACE methodology (AI-adoption consulting).
`/lace/show` idempotently ensures a dedicated project + goal board + 17
objective goals (3 nuclei) and renders honeycomb panels colored by each
goal's progress, with maturity KPIs and an attention radar.

**Single source of truth:** scores live in Leantime goals
(`zp_canvas_items.currentValue`, 0–100). Anything the plugin adds reads and
writes through core services (`Goalcanvas`), never raw SQL into core tables.

## Layout

```
register.php               events: languages, menu, (cron via consolekernel.schedule.cron)
Controllers/Show.php       /lace/show — thin, calls services only
Services/Lace.php          PluginInterface lifecycle (install/uninstall/enable/disable)
Services/Dashboard.php     data assembly: NUCLEI const, ensure* bootstrap, scoreColor()
Templates/show.blade.php   view (namespace `lace`, referenced as `lace.show`)
Language/{en-US,pt-BR}.ini UI strings (en-US = base; both must stay in sync)
Docs/                      ROADMAP.md (plan) + plugin-development.md (conventions learned)
```

## Plugin-specific rules

- **Version-specific conventions (differ from official docs!):** views in
  `Templates/` (NOT `Views/`), referenced as `lace.show` (two dot-segments,
  NOT `plugins.lace.show`); `Services/Lace.php implements PluginInterface`
  is required by `plugin:install`; composer.json needs `homepage` + `authors`
  and the CLI matches its `name` (`leantime/lace`), not the folder.
- **NUCLEI/goal titles are business data in pt-BR** — they must match the
  goals seeded in the DB. Never translate or rename them casually; renames
  require a data migration of existing goals.
- **i18n:** put every new UI string in BOTH ini files.
  `Registration::registerLanguageFiles()` alone is not enough: it relies on
  `session('usersettings.language')`, which stays unset for sessions
  bootstrapped on the login screen (Localization caches early). register.php
  therefore merges the file matching `Language::getCurrentLanguage()`
  (user → company → config default, `pt-BR` in this fork) at priority 6.
- **New tables:** prefix `zp_lace_`, created in `Services/Lace::install()`
  with `Illuminate\Support\Facades\Schema` (see core
  `Install/Services/SchemaBuilder.php` for style), dropped in `uninstall()`.
- **Cron:** register on `leantime.core.console.consolekernel.schedule.cron`
  (see `app/Domain/Reports/register.php` for the exact pattern).
- **HTMX events:** client events use the `lt:lace:{entity}.{verb}` naming via
  an enum in `Htmx/` implementing `HtmxEvent` (`InteractsWithHtmxEvents`).
  Reuse core `lt:ui:*` events (Notify etc.), never mint new ui commands.
- **Authorization is fail-closed** — canvas tables are shared across all
  canvas types with one id sequence (IDOR caution in root CLAUDE.md).
  Resolve the board's real project and authorize against it; never fall back
  to `session('currentProject')`.
- Root-repo rules that bite here: controllers → services only; strict types +
  phpDoc (`@api` on RPC-exposed service methods); `dtHelper()`/CarbonImmutable
  for dates; `Log::` facade, never `error_log()`.

## Dev environment & verification loop

- Stack: `docker compose -f .dev/docker-compose.yaml` (app at
  http://localhost:5080 and by LAN IP — keep `LEAN_APP_URL` empty so the
  host adapts; the user tests from another machine by IP).
- **Run the Leantime CLI as www-data**:
  `docker exec -u www-data -i leantime-dev sh -c 'cd /var/www/html && php bin/leantime …'`
  (root-run CLI writes root-owned cache files that 500 the web requests;
  fix: `chown -R 33:33 storage bootstrap/cache` inside the container).
- After changing views/register/config, clear caches:
  `rm -f bootstrap/cache/*.php storage/framework/{composerPaths,viewPaths}.php`
  and purge `storage/framework/{cache,views}` (keep `.gitignore` files).
- Verify end-to-end, not just lint: authenticated curl of `/lace/show`
  (expect 200, grep for rendered strings) and a Selenium screenshot via the
  dev stack's `selenium` service (WebDriver on its mapped port; navigate as
  `http://leantime-dev:8080` from inside the compose network).
- Seeding demo scores via mysql CLI: always pass
  `--default-character-set=utf8mb4` or accented goal titles (Visão, Adoção…)
  silently fail to match in `WHERE`.
- UI work: iterate on an HTML mockup (published as a Claude Artifact) until
  approved, then port to Blade.

## Testing & style

- `make test-code-style` (Pint) and `make phpstan` before committing.
- Unit tests (Codeception) for calculation/decision logic; acceptance smoke
  for routes (auth + 200 + key strings).
- Git: one branch per ROADMAP epic off `master`
  (`feature/lace-<epic>`), small descriptive commits, push when the epic's
  DoD is met.
