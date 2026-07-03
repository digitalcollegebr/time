# Leantime Plugin Template (corrected for this fork)

A generic, **working** starting point for new Leantime plugins in this repo.
It already bakes in the three corrections needed for this Leantime version
(3.6.x), which the official guide at
<https://docs.leantime.io/development/plugin-development> gets wrong:

1. Views live in **`Templates/`** (not `Views/`) — the plugin's view namespace
   is `strtolower(foldername)` → `app/Plugins/{Folder}/Templates`
   (`app/Core/UI/ViewsServiceProvider.php`).
2. Views are referenced as **`{lower}.show`** (two dot-segments), not
   `plugins.{lower}.show` — `Template::parseViewPath()` only uses the first two
   segments.
3. A **`Services/{Folder}.php` implementing `PluginInterface`** is required —
   `plugin:install` instantiates `…\Services\{Folder}` and calls `install()`
   (`app/Domain/Plugins/Services/Plugins.php`).

Also: `composer.json` `name` is `leantime/{lower}` (what the CLI matches) and
must include `homepage` + `authors` (the discovery code reads them).

## Tokens

Files use two placeholders you replace when scaffolding:

| Token                | Meaning                          | Example (`Reports`) |
|----------------------|----------------------------------|---------------------|
| `__PLUGIN_STUDLY__`  | StudlyCase folder / class name    | `Reports`           |
| `__PLUGIN_LOWER__`   | lowercase (url, view ns, i18n)    | `reports`           |

## Scaffold a new plugin

Each plugin gets its **own branch off `master`** (do not copy a plugin branch —
copy this folder):

```bash
git checkout master && git pull
git checkout -b feature/plugin-reports

# From the repo root — one command via the helper:
tools/new-plugin.sh Reports        # StudlyCase name

# ...or do it by hand:
NAME=Reports
LOWER=$(printf '%s' "$NAME" | tr '[:upper:]' '[:lower:]')
cp -r tools/plugin-template "app/Plugins/$NAME"
mv "app/Plugins/$NAME/README.md" "app/Plugins/$NAME/Docs/README.md" 2>/dev/null || true
mv "app/Plugins/$NAME/Services/__PLUGIN_STUDLY__.php" "app/Plugins/$NAME/Services/$NAME.php"
grep -rlZ '__PLUGIN_' "app/Plugins/$NAME" | xargs -0 sed -i "s/__PLUGIN_STUDLY__/$NAME/g; s/__PLUGIN_LOWER__/$LOWER/g"
```

## Install, enable, verify (inside the dev container)

> **Always run the CLI as `www-data`** (`docker exec -u www-data`). The web
> server runs as `www-data`; if you run the CLI as root it writes root-owned
> cache files under `storage/framework/cache` that the next web request cannot
> overwrite → `Permission denied` 500s.

```bash
LOWER=reports
docker exec -u www-data -i leantime-dev sh -c "cd /var/www/html && printf 'yes\n' | php bin/leantime plugin:install leantime/$LOWER"
docker exec -u www-data -i leantime-dev sh -c "cd /var/www/html && printf 'yes\n' | php bin/leantime plugin:enable  leantime/$LOWER"
# After adding/moving view paths, clear the caches so the view namespace is rediscovered:
docker exec -u www-data leantime-dev sh -c 'cd /var/www/html && rm -f bootstrap/cache/*.php storage/framework/composerPaths.php storage/framework/viewPaths.php && find storage/framework/cache -type f ! -name .gitignore -delete && find storage/framework/views -type f ! -name .gitignore -delete'
docker exec -u www-data leantime-dev sh -c 'cd /var/www/html && php bin/leantime plugin:list'
```

Then browse to `/{lower}/show` (e.g. `/reports/show`) or the item in the
personal menu.
