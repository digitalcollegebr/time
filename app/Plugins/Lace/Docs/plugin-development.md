# Lace Plugin

A Leantime plugin scaffolded from the official
[Plugin Development guide](https://docs.leantime.io/development/plugin-development)
and adapted to the conventions this Leantime version (3.6.x fork) actually
enforces in its source.

## Structure

```
app/Plugins/Lace/
├── bootstrap.php           # Plugin initialization (stub; only register.php is auto-loaded)
├── composer.json           # Plugin metadata (type: leantime-plugin, PSR-4 namespace)
├── register.php            # Event/feature registration (languages + menu)
├── Controllers/
│   └── Show.php            # Main controller, reachable at /lace/show
├── Services/
│   └── Lace.php            # PluginInterface lifecycle hooks (install/enable/…)
├── Templates/
│   └── show.blade.php      # Blade view, namespace `lace` → lace.show
├── Language/
│   └── en-US.ini           # English translations
└── Docs/
    └── plugin-development.md
```

## How it works

- **composer.json** — declares the plugin. `type` is `leantime-plugin` and the
  PSR-4 autoload maps `Leantime\Plugins\Lace\` to the plugin root. Note
  `name` (`leantime/lace`) is what the CLI matches, so install/enable use that
  name, and `homepage`/`authors` are required by the plugin discovery code.
- **register.php** — loaded early by the `PluginManager`. Builds a
  `Registration` helper (`pluginId` = folder name `Lace`), registers the
  language file and adds a menu item in the `personal` section → `/lace/show`.
- **Services/Lace.php** — implements `PluginInterface`. The plugin manager
  instantiates `Leantime\Plugins\Lace\Services\Lace` and calls its lifecycle
  hooks on install/uninstall/enable/disable.
- **Controllers/Show.php** — extends the base `Controller`. The frontcontroller
  routes `/lace/show` to `Show::get()`, which renders the view via
  `$this->tpl->display('lace.show')`.
- **Templates/show.blade.php** — Blade view extending the main `$layout`; all
  user-facing strings come from the language file via `__()`.
- **Language/en-US.ini** — INI translations, registered in `register.php`.

## Divergences from the official guide (and why)

The official guide targets a different Leantime version. Verified against this
repo's source, three things had to change to make the plugin load and render:

1. **Views live in `Templates/`, not `Views/`.** `ViewsServiceProvider::discoverViewPaths()`
   registers a view namespace `strtolower(foldername)` (= `lace`) pointing at
   `app/Plugins/Lace/Templates` (see `app/Core/UI/ViewsServiceProvider.php`).
2. **View reference is `lace.show`, not `plugins.lace.show`.** `Template::parseViewPath()`
   only uses the first two dot-segments (`module.name`), so a three-segment name
   drops the last part. `lace.show` resolves to the `lace::show` namespace view.
3. **A `Services/Lace.php` implementing `PluginInterface` is required.** The
   plugin manager instantiates `…\Services\{Foldername}` and calls `install()`
   during `plugin:install` (see `app/Domain/Plugins/Services/Plugins.php`).

## Install / enable (inside the dev container)

```bash
php bin/leantime plugin:install leantime/lace   # confirm with "yes"
php bin/leantime plugin:enable  leantime/lace   # confirm with "yes"
php bin/leantime plugin:list                    # -> Installed: yes, Enabled: yes
```

Then open the "Lace plugin" item in the personal menu, or browse to `/lace/show`.
```
