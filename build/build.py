from __future__ import annotations

import hashlib
import zipfile
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
VERSION = (ROOT / "VERSION").read_text(encoding="utf-8").strip()
LIB_SRC = ROOT / "src/lib_xdecarocore"
LEGACY_MANIFEST = ROOT / "src/lib_xdecarocorelegacy/xdecarocorelegacy.xml"
COMPONENT_SRC = ROOT / "src/com_xdecarocore"
PLUGIN_SRC = ROOT / "src/plg_system_xdecarocore"
PACKAGE_MANIFEST = ROOT / "package/pkg_xdecarocore/pkg_xdecarocore.xml"
DIST = ROOT / "dist"
FIXED_TIME = (1980, 1, 1, 0, 0, 0)


def add_bytes(archive: zipfile.ZipFile, arcname: str, data: bytes) -> None:
    info = zipfile.ZipInfo(arcname, FIXED_TIME)
    info.compress_type = zipfile.ZIP_DEFLATED
    info.create_system = 3
    info.external_attr = 0o644 << 16
    archive.writestr(info, data, compress_type=zipfile.ZIP_DEFLATED, compresslevel=9)


def zip_directory(source: Path, target: Path) -> None:
    with zipfile.ZipFile(target, "w") as archive:
        for path in sorted(source.rglob("*"), key=lambda item: item.as_posix()):
            if not path.is_file() or path.name == ".DS_Store":
                continue
            add_bytes(archive, path.relative_to(source).as_posix(), path.read_bytes())


def zip_legacy_library(target: Path) -> None:
    with zipfile.ZipFile(target, "w") as archive:
        add_bytes(archive, "xdecarocorelegacy.xml", LEGACY_MANIFEST.read_bytes())
        source_root = LIB_SRC / "src"
        for path in sorted(source_root.rglob("*"), key=lambda item: item.as_posix()):
            if not path.is_file() or path.name == ".DS_Store":
                continue
            add_bytes(archive, (Path("src") / path.relative_to(source_root)).as_posix(), path.read_bytes())


def sha256(path: Path) -> str:
    return hashlib.sha256(path.read_bytes()).hexdigest()


if not VERSION:
    raise SystemExit("VERSION is empty")

if not LEGACY_MANIFEST.is_file():
    raise SystemExit("Legacy Core namespace manifest is missing")

if not (COMPONENT_SRC / "xdecarocore.xml").is_file():
    raise SystemExit("Core administrator component manifest is missing")

DIST.mkdir(exist_ok=True)
for old in DIST.glob("*.zip"):
    old.unlink()
for old in DIST.glob("SHA256SUMS.txt"):
    old.unlink()

library_zip = DIST / f"lib_xdecarocore_{VERSION}.zip"
legacy_library_zip = DIST / f"lib_xdecarocorelegacy_{VERSION}.zip"
component_zip = DIST / f"com_xdecarocore_{VERSION}.zip"
plugin_zip = DIST / f"plg_system_xdecarocore_{VERSION}.zip"
package_zip = DIST / f"pkg_xdecarocore_{VERSION}.zip"

zip_directory(LIB_SRC, library_zip)
zip_legacy_library(legacy_library_zip)
zip_directory(COMPONENT_SRC, component_zip)
zip_directory(PLUGIN_SRC, plugin_zip)

with zipfile.ZipFile(package_zip, "w") as archive:
    add_bytes(archive, "pkg_xdecarocore.xml", PACKAGE_MANIFEST.read_bytes())
    add_bytes(archive, "lib_xdecarocore.zip", library_zip.read_bytes())
    add_bytes(archive, "lib_xdecarocorelegacy.zip", legacy_library_zip.read_bytes())
    add_bytes(archive, "com_xdecarocore.zip", component_zip.read_bytes())
    add_bytes(archive, "plg_system_xdecarocore.zip", plugin_zip.read_bytes())

artifacts = [library_zip, legacy_library_zip, component_zip, plugin_zip, package_zip]
(DIST / "SHA256SUMS.txt").write_text(
    "".join(f"{sha256(path)}  {path.name}\n" for path in artifacts),
    encoding="utf-8",
)

for path in artifacts:
    print(path)
