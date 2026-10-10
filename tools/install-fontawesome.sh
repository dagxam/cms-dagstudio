#!/usr/bin/env bash
# DAG STUDIO CMS: локальная версия Font Awesome Free 6.7.2 (не зависит от CDN).
set -euo pipefail
cd "$(dirname "$0")/.."
tmp="$(mktemp -d)"
trap 'rm -rf "$tmp"' EXIT
npm pack --ignore-scripts --silent --pack-destination "$tmp" '@fortawesome/fontawesome-free@6.7.2' >/dev/null
archive="$(find "$tmp" -maxdepth 1 -name '*.tgz' -type f -print -quit)"
test -n "$archive"
tar -xzf "$archive" -C "$tmp"
mkdir -p assets/fontawesome/css assets/fontawesome/webfonts
cp "$tmp/package/css/all.min.css" assets/fontawesome/css/all.min.css
cp "$tmp/package/webfonts/"* assets/fontawesome/webfonts/
if test -f "$tmp/package/LICENSE.txt"; then cp "$tmp/package/LICENSE.txt" assets/fontawesome/LICENSE.txt; fi
test -s assets/fontawesome/css/all.min.css
test -s assets/fontawesome/webfonts/fa-solid-900.woff2
test -s assets/fontawesome/webfonts/fa-brands-400.woff2
test -s assets/fontawesome/webfonts/fa-regular-400.woff2
echo 'Font Awesome Free 6.7.2: CSS и шрифты подготовлены для размещения на самом сайте.'
