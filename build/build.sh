#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
VERSION="$(tr -d '[:space:]' < "$ROOT/VERSION")"
PACKAGE_SRC="$ROOT/package/pkg_core"
UPDATE_FEED="$ROOT/updates/pkg_core.xml"
LEGACY_UPDATE_BRIDGE="$ROOT/updates/pkg_xdecarocore.xml"
DIST="$ROOT/dist"

command -v php >/dev/null 2>&1 || { echo "PHP CLI is required." >&2; exit 1; }
command -v python3 >/dev/null 2>&1 || { echo "Python 3 is required." >&2; exit 1; }

[[ "$VERSION" == "2.2.0" ]] || { echo "Core shared-admin release must be 2.2.0." >&2; exit 1; }
[[ -f "$PACKAGE_SRC/pkg_core.xml" ]] || { echo "Canonical Core package manifest is missing." >&2; exit 1; }
[[ -f "$UPDATE_FEED" ]] || { echo "Canonical Core update feed is missing." >&2; exit 1; }
[[ -f "$LEGACY_UPDATE_BRIDGE" ]] || { echo "Legacy Core update bridge is missing." >&2; exit 1; }
php -r 'if (PHP_VERSION_ID < 80300) { fwrite(STDERR, "Core 2.2.0 requires PHP 8.3 or later.\n"); exit(1); }'

while IFS= read -r -d '' php_file; do
    php -l "$php_file" >/dev/null
done < <(find "$ROOT/src" "$ROOT/tests" "$ROOT/build" "$ROOT/package" -type f -name '*.php' -print0)

php "$ROOT/tests/smoke.php"
php "$ROOT/tests/assets.php"
php "$ROOT/tests/integration.php"
php "$ROOT/tests/dashboard.php"
php "$ROOT/tests/locations.php"
php "$ROOT/tests/core-2.1-package-contract.php"
php "$ROOT/tests/package-naming-migration.php"
php "$ROOT/tests/core-2.2-admin-ui-contract.php"
php "$ROOT/tests/core-2.2-dashboard-shared-ui.php"
php "$ROOT/tests/core-2.2-release-readiness.php"

python3 "$ROOT/build/build.py"

python3 - "$DIST" "$VERSION" <<'PY'
import sys
import zipfile
from pathlib import Path

dist = Path(sys.argv[1])
version = sys.argv[2]
artifacts = {
    "package": dist / f"pkg_core_{version}.zip",
    "library": dist / f"lib_xdecarocore_{version}.zip",
    "component": dist / f"com_xdecarocore_{version}.zip",
    "plugin": dist / f"plg_system_xdecarocore_{version}.zip",
}

for label, path in artifacts.items():
    if not path.is_file():
        raise SystemExit(f"Missing {label} artifact: {path}")
    with zipfile.ZipFile(path) as archive:
        bad = archive.testzip()
        if bad:
            raise SystemExit(f"Corrupt ZIP member {bad} in {path}")

with zipfile.ZipFile(artifacts["package"]) as archive:
    expected = {
        "pkg_core.xml",
        "script.php",
        "lib_xdecarocore.zip",
        "com_xdecarocore.zip",
        "plg_system_xdecarocore.zip",
    }
    if set(archive.namelist()) != expected:
        raise SystemExit("Core package contents changed unexpectedly")

with zipfile.ZipFile(artifacts["plugin"]) as archive:
    required = {
        "xdecarocore.xml",
        "services/provider.php",
        "src/Extension/CorePlugin.php",
        "media/joomla.asset.json",
        "media/css/core.css",
        "media/css/components.css",
        "media/css/admin.css",
    }
    missing = required.difference(archive.namelist())
    if missing:
        raise SystemExit(f"Core plugin shared UI is incomplete: {sorted(missing)}")
    registry = archive.read("media/joomla.asset.json").decode("utf-8")
    if '"name": "xdecaro.admin"' not in registry:
        raise SystemExit("Plugin registry does not expose xdecaro.admin")

with zipfile.ZipFile(artifacts["component"]) as archive:
    required = {
        "xdecarocore.xml",
        "admin/config.xml",
        "admin/src/View/Dashboard/HtmlView.php",
        "media/joomla.asset.json",
        "media/css/admin.css",
        "media/js/admin.js",
    }
    missing = required.difference(archive.namelist())
    if missing:
        raise SystemExit(f"Core component is incomplete: {sorted(missing)}")
    if "media/css/responsive.css" in archive.namelist():
        raise SystemExit("Retired responsive.css must not be packaged")
PY

printf 'Built and validated Core %s\n' "$VERSION"
cat "$DIST/SHA256SUMS.txt"
