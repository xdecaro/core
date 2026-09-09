#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
VERSION="$(tr -d '[:space:]' < "$ROOT/VERSION")"
LIB_SRC="$ROOT/src/lib_xdecarocore"
LEGACY_MANIFEST="$ROOT/src/lib_xdecarocorelegacy/xdecarocorelegacy.xml"
COMPONENT_SRC="$ROOT/src/com_xdecarocore"
PLUGIN_SRC="$ROOT/src/plg_system_xdecarocore"
PACKAGE_SRC="$ROOT/package/pkg_xdecarocore"
PACKAGE_SCRIPT="$PACKAGE_SRC/script.php"
ASSET_REGISTRY="$PLUGIN_SRC/media/joomla.asset.json"
COMPONENT_ASSET_REGISTRY="$COMPONENT_SRC/media/joomla.asset.json"
UPDATE_FEED="$ROOT/updates/pkg_xdecarocore.xml"
CHANGELOG_XML="$ROOT/updates/changelog.xml"
DIST="$ROOT/dist"

command -v php >/dev/null 2>&1 || { echo "PHP CLI is required." >&2; exit 1; }
command -v python3 >/dev/null 2>&1 || { echo "Python 3 is required." >&2; exit 1; }

[[ -n "$VERSION" ]] || { echo "VERSION is empty." >&2; exit 1; }
[[ -f "$PACKAGE_SCRIPT" ]] || { echo "Core package installer script is missing." >&2; exit 1; }

while IFS= read -r -d '' php_file; do
    php -l "$php_file" >/dev/null
done < <(find "$ROOT/src" "$ROOT/tests" "$ROOT/build" "$ROOT/package" -type f -name '*.php' -print0)

php -r '
$version = trim(file_get_contents($argv[1]));
$lib = simplexml_load_file($argv[2]);
$legacy = simplexml_load_file($argv[3]);
$component = simplexml_load_file($argv[4]);
$plugin = simplexml_load_file($argv[5]);
$package = simplexml_load_file($argv[6]);
foreach ([$lib, $legacy, $component, $plugin, $package] as $manifest) {
    if ($manifest === false || trim((string) $manifest->version) !== $version) {
        fwrite(STDERR, "Manifest version mismatch or invalid XML.\n"); exit(1);
    }
}
if (trim((string) $lib->namespace) !== "xdecaro\\Core") {
    fwrite(STDERR, "Canonical Core namespace must be xdecaro\\Core.\n"); exit(1);
}
if (trim((string) $legacy->namespace) !== "Xdecaro\\Core"
    || trim((string) $legacy->libraryname) !== "xdecaro/corelegacy") {
    fwrite(STDERR, "Legacy Core namespace compatibility manifest is invalid.\n"); exit(1);
}
if (trim((string) $component->namespace) !== "xdecaro\\Component\\Core") {
    fwrite(STDERR, "Core administrator component namespace is not canonical.\n"); exit(1);
}
if (trim((string) $component->administration->menu) !== "COM_XDECAROCORE_MENU") {
    fwrite(STDERR, "Core administrator menu entry is missing.\n"); exit(1);
}
if (trim((string) $component->media["destination"]) !== "com_xdecarocore"
    || trim((string) $component->media["folder"]) !== "media") {
    fwrite(STDERR, "Core administrator media destination is missing or incorrect.\n"); exit(1);
}
if (trim((string) $plugin->namespace) !== "xdecaro\\Plugin\\System\\XdecaroCore") {
    fwrite(STDERR, "Core plugin namespace is not canonical.\n"); exit(1);
}
if (trim((string) $plugin->media["destination"]) !== "plg_system_xdecarocore"
    || trim((string) $plugin->media["folder"]) !== "media") {
    fwrite(STDERR, "Core plugin media destination is missing or incorrect.\n"); exit(1);
}
if (trim((string) $package->scriptfile) !== "script.php" || !is_file($argv[11])) {
    fwrite(STDERR, "Core package installer script is missing or not declared.\n"); exit(1);
}
$files = [];
foreach ($package->files->file as $file) {
    $files[(string) $file] = [(string) $file["type"], (string) $file["id"]];
}
$expectedChildren = [
    "lib_xdecarocore.zip" => ["library", "xdecaro/core"],
    "lib_xdecarocorelegacy.zip" => ["library", "xdecaro/corelegacy"],
    "com_xdecarocore.zip" => ["component", "com_xdecarocore"],
    "plg_system_xdecarocore.zip" => ["plugin", "xdecarocore"],
];
foreach ($expectedChildren as $name => $meta) {
    if (!isset($files[$name]) || $files[$name] !== $meta) {
        fwrite(STDERR, "Core package child metadata mismatch: {$name}\n"); exit(1);
    }
}
$server = trim((string) $package->updateservers->server);
if ($server !== "https://raw.githubusercontent.com/xdecaro/core/main/updates/pkg_xdecarocore.xml") {
    fwrite(STDERR, "Package update server is missing or incorrect.\n"); exit(1);
}
$assets = json_decode(file_get_contents($argv[7]), true);
if (!is_array($assets) || ($assets["name"] ?? "") !== "plg_system_xdecarocore" || ($assets["version"] ?? "") !== $version) {
    fwrite(STDERR, "Invalid Core asset registry metadata.\n"); exit(1);
}
$found = [];
foreach (($assets["assets"] ?? []) as $asset) {
    if (isset($asset["name"], $asset["type"], $asset["uri"])) $found[$asset["name"]] = $asset;
}
$expectedAssets = [
    "xdecaro.core" => "plg_system_xdecarocore/core.css",
    "xdecaro.components" => "plg_system_xdecarocore/components.css",
];
foreach ($expectedAssets as $name => $uri) {
    if (!isset($found[$name]) || $found[$name]["type"] !== "style" || $found[$name]["uri"] !== $uri || ($found[$name]["version"] ?? "") !== $version) {
        fwrite(STDERR, "Required Core style asset is missing or inconsistent: {$name}\n"); exit(1);
    }
}
$componentAssets = json_decode(file_get_contents($argv[8]), true);
if (!is_array($componentAssets) || ($componentAssets["name"] ?? "") !== "com_xdecarocore" || ($componentAssets["version"] ?? "") !== $version) {
    fwrite(STDERR, "Invalid Core administrator asset registry metadata.\n"); exit(1);
}
$componentFound = [];
foreach (($componentAssets["assets"] ?? []) as $asset) {
    if (($asset["name"] ?? "") === "com_xdecarocore.admin" && isset($asset["type"], $asset["uri"])) {
        $componentFound[$asset["type"]] = $asset;
    }
}
$expectedComponentAssets = [
    "style" => "com_xdecarocore/admin.css",
    "script" => "com_xdecarocore/admin.js",
];
foreach ($expectedComponentAssets as $type => $uri) {
    $asset = $componentFound[$type] ?? null;
    if (!is_array($asset) || ($asset["uri"] ?? "") !== $uri || ($asset["version"] ?? "") !== $version) {
        fwrite(STDERR, "Core administrator {$type} asset is missing or inconsistent.\n"); exit(1);
    }
    if (strpos($uri, "/css/") !== false || strpos($uri, "/js/") !== false) {
        fwrite(STDERR, "Core administrator Web Asset Manager URI duplicates a Joomla media type directory.\n"); exit(1);
    }
}
$feed = simplexml_load_file($argv[9]);
if ($feed === false || count($feed->update) < 1) {
    fwrite(STDERR, "Invalid Core update feed.\n"); exit(1);
}
foreach ($feed->update as $update) {
    if (trim((string) $update->version) !== $version
        || trim((string) $update->element) !== "pkg_xdecarocore"
        || trim((string) $update->type) !== "package"
        || trim((string) $update->client) !== "site") {
        fwrite(STDERR, "Core update feed metadata is inconsistent.\n"); exit(1);
    }
    $expectedUrl = "https://github.com/xdecaro/core/releases/download/v{$version}/pkg_xdecarocore_{$version}.zip";
    if (trim((string) $update->downloads->downloadurl) !== $expectedUrl) {
        fwrite(STDERR, "Core update feed download URL is inconsistent.\n"); exit(1);
    }
    if (trim((string) $update->targetplatform["name"]) !== "joomla"
        || trim((string) $update->targetplatform["version"]) !== "((4|5|6)\\.[0-9]+)") {
        fwrite(STDERR, "Core targetplatform must support Joomla 4, 5 and 6.\n"); exit(1);
    }
}
if (simplexml_load_file($argv[10]) === false) {
    fwrite(STDERR, "Invalid Core changelog XML.\n"); exit(1);
}
' "$ROOT/VERSION" "$LIB_SRC/xdecarocore.xml" "$LEGACY_MANIFEST" "$COMPONENT_SRC/xdecarocore.xml" "$PLUGIN_SRC/xdecarocore.xml" "$PACKAGE_SRC/pkg_xdecarocore.xml" "$ASSET_REGISTRY" "$COMPONENT_ASSET_REGISTRY" "$UPDATE_FEED" "$CHANGELOG_XML" "$PACKAGE_SCRIPT"

php "$ROOT/tests/smoke.php"
php "$ROOT/tests/assets.php"
php "$ROOT/tests/integration.php"
php "$ROOT/tests/namespace-compat.php"
php "$ROOT/tests/dashboard.php"
python3 "$ROOT/build/build.py"

python3 - "$DIST" "$VERSION" <<'PY'
import sys
import zipfile
from pathlib import Path

dist = Path(sys.argv[1])
version = sys.argv[2]
library = dist / f"lib_xdecarocore_{version}.zip"
legacy = dist / f"lib_xdecarocorelegacy_{version}.zip"
component = dist / f"com_xdecarocore_{version}.zip"
plugin = dist / f"plg_system_xdecarocore_{version}.zip"
package = dist / f"pkg_xdecarocore_{version}.zip"
artifacts = [library, legacy, component, plugin, package]
for path in artifacts:
    if not path.is_file():
        raise SystemExit(f"Missing build artifact: {path}")
    with zipfile.ZipFile(path) as archive:
        bad = archive.testzip()
        if bad:
            raise SystemExit(f"Corrupt ZIP member {bad} in {path}")

required_sources = {
    "src/Version.php",
    "src/Asset/AssetService.php",
    "src/Integration/EntityReference.php",
    "src/Integration/RelationReference.php",
    "src/Integration/Capability.php",
    "src/Integration/IntegrationEvent.php",
}
with zipfile.ZipFile(library) as archive:
    names = set(archive.namelist())
    if not required_sources.issubset(names) or "xdecarocore.xml" not in names:
        raise SystemExit("Canonical Core library is incomplete")
    manifest = archive.read("xdecarocore.xml").decode()
    if "<namespace path=\"src\">xdecaro\\Core</namespace>" not in manifest:
        raise SystemExit("Canonical Core namespace mapping is incorrect")

with zipfile.ZipFile(legacy) as archive:
    names = set(archive.namelist())
    if not required_sources.issubset(names) or "xdecarocorelegacy.xml" not in names:
        raise SystemExit("Legacy Core compatibility library is incomplete")
    manifest = archive.read("xdecarocorelegacy.xml").decode()
    if "<namespace path=\"src\">Xdecaro\\Core</namespace>" not in manifest:
        raise SystemExit("Legacy Core namespace mapping is incorrect")
    with zipfile.ZipFile(library) as canonical:
        if archive.read("src/Integration/EntityReference.php") != canonical.read("src/Integration/EntityReference.php"):
            raise SystemExit("Legacy compatibility sources differ from canonical Core sources")

with zipfile.ZipFile(component) as archive:
    required = {
        "xdecarocore.xml",
        "admin/services/provider.php",
        "admin/src/Controller/DisplayController.php",
        "admin/src/Service/EcosystemService.php",
        "admin/src/View/Dashboard/HtmlView.php",
        "admin/tmpl/dashboard/default.php",
        "admin/tmpl/dashboard/products.php",
        "admin/tmpl/dashboard/extensions.php",
        "admin/tmpl/dashboard/updates.php",
        "admin/tmpl/dashboard/diagnostics.php",
        "admin/tmpl/dashboard/information.php",
        "media/joomla.asset.json",
        "media/css/admin.css",
        "media/js/admin.js",
    }
    if not required.issubset(set(archive.namelist())):
        raise SystemExit("Core administrator component ZIP is incomplete")

with zipfile.ZipFile(plugin) as archive:
    required = {"media/joomla.asset.json", "media/css/core.css", "media/css/components.css"}
    if not required.issubset(set(archive.namelist())):
        raise SystemExit("Core plugin ZIP is missing shared media assets")

with zipfile.ZipFile(package) as archive:
    expected = {"pkg_xdecarocore.xml", "script.php", "lib_xdecarocore.zip", "lib_xdecarocorelegacy.zip", "com_xdecarocore.zip", "plg_system_xdecarocore.zip"}
    if set(archive.namelist()) != expected:
        raise SystemExit("Core package contains unexpected or missing files")
    manifest = archive.read("pkg_xdecarocore.xml").decode()
    if "<scriptfile>script.php</scriptfile>" not in manifest:
        raise SystemExit("Core package installer script is not declared")
PY

printf 'Built and validated Core by xdecaro %s\n' "$VERSION"
cat "$DIST/SHA256SUMS.txt"
