#!/usr/bin/env bash
set -euo pipefail

STAGING_DIR="/usr/src/wordpress-plugins"
TARGET_DIR="/var/www/wp-content/plugins"

if [ -d "$STAGING_DIR" ] && [ -d "$TARGET_DIR" ]; then
    echo "[dressme-entrypoint] Syncing repo-managed plugins from image to volume..."

    for plugin_path in "$STAGING_DIR"/*/; do
        [ -d "$plugin_path" ] || continue
        plugin_name="$(basename "$plugin_path")"
        echo "[dressme-entrypoint]   - ${plugin_name}"
        rsync -a --delete "$plugin_path" "$TARGET_DIR/$plugin_name/"
    done

    find "$STAGING_DIR" -maxdepth 1 -type f -exec cp -a {} "$TARGET_DIR/" \;

    chown -R www-data:www-data "$TARGET_DIR"
    echo "[dressme-entrypoint] Plugin sync complete."
fi

exec docker-php-entrypoint "$@"
