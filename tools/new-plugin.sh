#!/usr/bin/env bash
#
# Scaffold a new Leantime plugin from tools/plugin-template into app/Plugins/.
#
# Usage:  tools/new-plugin.sh <StudlyName>
# Example: tools/new-plugin.sh Reports   ->   app/Plugins/Reports (url /reports/show)
#
# It copies the template, renames the lifecycle service to match the folder,
# and replaces the __PLUGIN_STUDLY__ / __PLUGIN_LOWER__ tokens. It does NOT
# install/enable the plugin — see the printed next steps for that.

set -euo pipefail

NAME="${1:-}"
if [[ -z "$NAME" ]]; then
    echo "Usage: tools/new-plugin.sh <StudlyName>   (e.g. Reports)" >&2
    exit 1
fi

if [[ ! "$NAME" =~ ^[A-Z][A-Za-z0-9]*$ ]]; then
    echo "Error: plugin name must be StudlyCase (start uppercase, letters/digits only): '$NAME'" >&2
    exit 1
fi

# Resolve repo root from this script's location (tools/ lives at the repo root).
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
TEMPLATE="$ROOT/tools/plugin-template"
DEST="$ROOT/app/Plugins/$NAME"

LOWER="$(printf '%s' "$NAME" | tr '[:upper:]' '[:lower:]')"

if [[ ! -d "$TEMPLATE" ]]; then
    echo "Error: template not found at $TEMPLATE" >&2
    exit 1
fi
if [[ -e "$DEST" ]]; then
    echo "Error: $DEST already exists" >&2
    exit 1
fi

cp -r "$TEMPLATE" "$DEST"

# The template README documents the scaffolding tokens themselves, so it must NOT
# be carried into the generated plugin (token replacement would corrupt it).
rm -f "$DEST/README.md"

# Lifecycle service class must be named after the folder.
mv "$DEST/Services/__PLUGIN_STUDLY__.php" "$DEST/Services/$NAME.php"

# Replace tokens across all files.
grep -rlZ '__PLUGIN_' "$DEST" | xargs -0 sed -i "s/__PLUGIN_STUDLY__/$NAME/g; s/__PLUGIN_LOWER__/$LOWER/g"

# Fresh, token-free docs stub for the generated plugin.
mkdir -p "$DEST/Docs"
cat > "$DEST/Docs/plugin-development.md" <<EOF
# $NAME Plugin

Scaffolded from \`tools/plugin-template\` (see that folder's README for the
Leantime-version-specific conventions this structure follows).

- Route: \`/$LOWER/show\` -> \`Controllers/Show.php::get()\`
- View: \`Templates/show.blade.php\` (namespace \`$LOWER\`, referenced as \`$LOWER.show\`)
- Lifecycle: \`Services/$NAME.php\` implements \`PluginInterface\`
- i18n: \`Language/en-US.ini\`
- Install/enable: \`php bin/leantime plugin:install leantime/$LOWER\` then \`plugin:enable\`
EOF

echo "Created app/Plugins/$NAME"
echo
echo "Next steps (dev container must be up). Run the CLI as www-data so cache"
echo "files stay writable by the web server (running as root breaks web requests):"
echo "  docker exec -u www-data -i leantime-dev sh -c \"cd /var/www/html && printf 'yes\\n' | php bin/leantime plugin:install leantime/$LOWER\""
echo "  docker exec -u www-data -i leantime-dev sh -c \"cd /var/www/html && printf 'yes\\n' | php bin/leantime plugin:enable  leantime/$LOWER\""
echo "  docker exec -u www-data leantime-dev sh -c 'cd /var/www/html && rm -f bootstrap/cache/*.php storage/framework/composerPaths.php storage/framework/viewPaths.php && find storage/framework/cache -type f ! -name .gitignore -delete && find storage/framework/views -type f ! -name .gitignore -delete'"
echo "  # then browse to /$LOWER/show"
