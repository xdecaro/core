#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
VERSION="$(tr -d '[:space:]' < "$ROOT/VERSION")"
LIB_SRC="$ROOT/src/lib_xdecarocore"
PLUGIN_SRC="$ROOT/src/plg_system_xdecarocore"
PACKAGE_SRC="$ROOT/package/pkg_xdecarocore"
ASSET_REGISTRY="$PLUGIN_SRC/media/joomla.asset.json"
UPDATE_FEED="$ROOT/updates/pkg_xdecarocore.xml"
CHANGELOG_XML="$ROOT/updates/changelog.xml"
DIST="$ROOT/dist"

command -v php >/dev/null 2>&1 || { echo "PHP CLI is required." >&2; exit 1; }
command -v python3 >/dev/null 2>&1 || { echo "Python 3 is required." >&2; exit 1; }

if [[ -z "$VERSION" ]]; then
    echo "VERSION is empty." >&2
    exit 1
fi

while IFS= read -r -d '' php_file; do
    php -l "$php_file" >/dev/null
done < <(find "$ROOT/src" "$ROOT/tests" "$ROOT/build" -type f -name '*.php' -print0)

php -r '
$version = trim(file_get_contents($argv[1]));
$manifests = array_slice($argv, 2, 3);
foreach ($manifests as $file) {
    libxml_use_internal_errors(true);
    $xml = simplexml_load_file($file);
    if ($xml === false) {
        fwrite(STDERR, "Invalid XML manifest: {$file}\n");
        exit(1);
    }
    if (trim((string) $xml->version) !== $version) {
        fwrite(STDERR, "Manifest version mismatch: {$file}\n");
        exit(1);
    }
}
$plugin = simplexml_load_file($manifests[1]);
if (trim((string) $plugin->media["destination"]) !== "plg_system_xdecarocore"
    || trim((string) $plugin->media["folder"]) !== "media") {
    fwrite(STDERR, "Core plugin media destination is missing or incorrect.\n");
    exit(1);
}
$package = simplexml_load_file($manifests[2]);
$server = trim((string) $package->updateservers->server);
if ($server !== "https://raw.githubusercontent.com/xdecaro/core/main/updates/pkg_xdecarocore.xml") {
    fwrite(STDERR, "Package update server is missing or incorrect.\n");
    exit(1);
}
$assetText = file_get_contents($argv[5]);
$assets = json_decode((string) $assetText, true);
if (!is_array($assets) || json_last_error() !== JSON_ERROR_NONE) {
    fwrite(STDERR, "Invalid Core joomla.asset.json.\n");
    exit(1);
}
if (($assets["name"] ?? "") !== "plg_system_xdecarocore" || ($assets["version"] ?? "") !== $version) {
    fwrite(STDERR, "Core asset registry metadata is inconsistent.\n");
    exit(1);
}
$found = [];
foreach (($assets["assets"] ?? []) as $asset) {
    if (isset($asset["name"], $asset["type"], $asset["uri"])) {
        $found[$asset["name"]] = $asset;
    }
}
$expected = [
    "xdecaro.core" => "plg_system_xdecarocore/core.css",
    "xdecaro.components" => "plg_system_xdecarocore/components.css",
];
foreach ($expected as $name => $uri) {
    if (!isset($found[$name]) || $found[$name]["type"] !== "style" || $found[$name]["uri"] !== $uri) {
        fwrite(STDERR, "Required Core style asset is missing or inconsistent: {$name}\n");
        exit(1);
    }
    if (($found[$name]["version"] ?? "") !== $version) {
        fwrite(STDERR, "Core style asset version mismatch: {$name}\n");
        exit(1);
    }
}
$feed = simplexml_load_file($argv[6]);
if ($feed === false || count($feed->update) < 1) {
    fwrite(STDERR, "Invalid Core update feed.\n");
    exit(1);
}
foreach ($feed->update as $update) {
    if (trim((string) $update->version) !== $version
        || trim((string) $update->element) !== "pkg_xdecarocore"
        || trim((string) $update->type) !== "package"
        || trim((string) $update->client) !== "site") {
        fwrite(STDERR, "Core update feed metadata is inconsistent.\n");
        exit(1);
    }
    $expectedUrl = "https://github.com/xdecaro/core/releases/download/v{$version}/pkg_xdecarocore_{$version}.zip";
    if (trim((string) $update->downloads->downloadurl) !== $expectedUrl) {
        fwrite(STDERR, "Core update feed download URL is inconsistent.\n");
        exit(1);
    }
    if (trim((string) $update->targetplatform["name"]) !== "joomla"
        || trim((string) $update->targetplatform["version"]) !== "((4|5|6)\\.[0-9]+)") {
        fwrite(STDERR, "Core targetplatform must support Joomla 4, 5 and 6.\n");
        exit(1);
    }
}
if (simplexml_load_file($argv[7]) === false) {
    fwrite(STDERR, "Invalid Core changelog XML.\n");
    exit(1);
}
' "$ROOT/VERSION" "$LIB_SRC/xdecarocore.xml" "$PLUGIN_SRC/xdecarocore.xml" "$PACKAGE_SRC/pkg_xdecarocore.xml" "$ASSET_REGISTRY" "$UPDATE_FEED" "$CHANGELOG_XML"

php "$ROOT/tests/smoke.php"
php "$ROOT/tests/assets.php"
php "$ROOT/tests/integration.php"
python3 "$ROOT/build/build.py"

python3 - "$DIST" "$VERSION" <<'PY'
import sys
import zipfile
from pathlib import Path

dist = Path(sys.argv[1])
version = sys.argv[2]
artifacts = [
    dist / f"lib_xdecarocore_{version}.zip",
    dist / f"plg_system_xdecarocore_{version}.zip",
    dist / f"pkg_xdecarocore_{version}.zip",
]
for path in artifacts:
    if not path.is_file():
        raise SystemExit(f"Missing build artifact: {path}")
    with zipfile.ZipFile(path) as archive:
        bad = archive.testzip()
        if bad:
            raise SystemExit(f"Corrupt ZIP member {bad} in {path}")

with zipfile.ZipFile(artifacts[0]) as library:
    required = {
        "src/Asset/AssetService.php",
        "src/Integration/Capability.php",
        "src/Integration/IntegrationEvent.php",
    }
    if not required.issubset(set(library.namelist())):
        raise SystemExit("Core library is missing required public integration contracts")

with zipfile.ZipFile(artifacts[1]) as plugin:
    required = {
        "media/joomla.asset.json",
        "media/css/core.css",
        "media/css/components.css",
    }
    if not required.issubset(set(plugin.namelist())):
        raise SystemExit("Core plugin ZIP is missing shared media assets")

with zipfile.ZipFile(artifacts[-1]) as package:
    expected = {"pkg_xdecarocore.xml", "lib_xdecarocore.zip", "plg_system_xdecarocore.zip"}
    if set(package.namelist()) != expected:
        raise SystemExit("Core package contains unexpected or missing files")
PY

printf 'Built and validated Core by xdecaro %s\n' "$VERSION"
cat "$DIST/SHA256SUMS.txt"
