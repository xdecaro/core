#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
LIB_SRC="$ROOT/src/lib_xdecarocore"
PLUGIN_SRC="$ROOT/src/plg_system_xdecarocore"
PACKAGE_SRC="$ROOT/package/pkg_xdecarocore"
DIST="$ROOT/dist"

VERSION="$(sed -n 's:.*<version>\([^<]*\)</version>.*:\1:p' "$LIB_SRC/xdecarocore.xml" | head -n 1)"
PLUGIN_VERSION="$(sed -n 's:.*<version>\([^<]*\)</version>.*:\1:p' "$PLUGIN_SRC/xdecarocore.xml" | head -n 1)"
PACKAGE_VERSION="$(sed -n 's:.*<version>\([^<]*\)</version>.*:\1:p' "$PACKAGE_SRC/pkg_xdecarocore.xml" | head -n 1)"

if [[ -z "$VERSION" || "$VERSION" != "$PLUGIN_VERSION" || "$VERSION" != "$PACKAGE_VERSION" ]]; then
    echo "Core manifest versions are missing or inconsistent." >&2
    exit 1
fi

command -v php >/dev/null 2>&1 || {
    echo "PHP CLI is required for validation." >&2
    exit 1
}

command -v zip >/dev/null 2>&1 || {
    echo "The zip command is required." >&2
    exit 1
}

while IFS= read -r -d '' php_file; do
    php -l "$php_file" >/dev/null
done < <(find "$ROOT/src" "$ROOT/tests" -type f -name '*.php' -print0)

php -r '
foreach (array_slice($argv, 1) as $file) {
    libxml_use_internal_errors(true);
    if (simplexml_load_file($file) === false) {
        fwrite(STDERR, "Invalid XML manifest: {$file}\n");
        exit(1);
    }
}
' "$LIB_SRC/xdecarocore.xml" "$PLUGIN_SRC/xdecarocore.xml" "$PACKAGE_SRC/pkg_xdecarocore.xml"

php "$ROOT/tests/smoke.php"

rm -rf "$DIST"
mkdir -p "$DIST"

TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"' EXIT

(
    cd "$LIB_SRC"
    zip -qr "$DIST/lib_xdecarocore_${VERSION}.zip" .
)

(
    cd "$PLUGIN_SRC"
    zip -qr "$DIST/plg_system_xdecarocore_${VERSION}.zip" .
)

mkdir -p "$TMP/package"
cp "$PACKAGE_SRC/pkg_xdecarocore.xml" "$TMP/package/pkg_xdecarocore.xml"
cp "$DIST/lib_xdecarocore_${VERSION}.zip" "$TMP/package/lib_xdecarocore.zip"
cp "$DIST/plg_system_xdecarocore_${VERSION}.zip" "$TMP/package/plg_system_xdecarocore.zip"

(
    cd "$TMP/package"
    zip -qr "$DIST/pkg_xdecarocore_${VERSION}.zip" .
)

printf 'Built Xdecaro Core %s:\n' "$VERSION"
printf '  %s\n' "$DIST/lib_xdecarocore_${VERSION}.zip"
printf '  %s\n' "$DIST/plg_system_xdecarocore_${VERSION}.zip"
printf '  %s\n' "$DIST/pkg_xdecarocore_${VERSION}.zip"
