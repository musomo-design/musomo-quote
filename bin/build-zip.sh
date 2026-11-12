#!/usr/bin/env bash
# Build a WordPress-installable ZIP of Musomo Quote.
#
# The Git checkout keeps development files such as .gitignore and .github.
# Plugin Check rejects those hidden files in a distributed package, so this
# script copies the plugin to a clean tree and zips only that tree.
#
# Usage:
#   bin/build-zip.sh
#   bin/build-zip.sh /path/to/musomo-quote-2.0.0.zip
#
# Default output: ../dist/musomo-quote-<version>.zip

set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
MAIN="$ROOT/musomo-quote.php"

if [[ ! -f "$MAIN" ]]; then
	echo "error: plugin bootstrap not found at $MAIN" >&2
	exit 1
fi

VERSION="$(sed -n 's/^[[:space:]]*\*[[:space:]]*Version:[[:space:]]*//p' "$MAIN" | head -n 1 | tr -d '[:space:]')"
if [[ ! "$VERSION" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
	echo "error: could not read a plugin version from $MAIN" >&2
	exit 1
fi

OUT="${1:-$(cd "$ROOT/.." && pwd)/dist/musomo-quote-${VERSION}.zip}"
STAGE="$(mktemp -d "${TMPDIR:-/tmp}/musomo-quote-zip.XXXXXX")"
PLUGIN_STAGE="$STAGE/musomo-quote"

cleanup() {
	if [[ -n "${STAGE:-}" && -d "$STAGE" ]]; then
		rm -rf "$STAGE"
	fi
}
trap cleanup EXIT

fail() {
	echo "error: $*" >&2
	rm -f "$OUT"
	exit 1
}

mkdir -p "$PLUGIN_STAGE" "$(dirname "$OUT")"

# Dot paths (.git, .github, .gitignore, .DS_Store, and any other hidden file)
# are development-only. Named excludes cover dev files that are not hidden.
rsync -a \
	--exclude '.*' \
	--exclude 'bin/' \
	--exclude 'node_modules/' \
	--exclude 'vendor/' \
	--exclude 'tests/' \
	--exclude 'test/' \
	--exclude '*.zip' \
	--exclude '*.log' \
	--exclude '*.swp' \
	--exclude '*.swo' \
	--exclude '*~' \
	--exclude 'composer.json' \
	--exclude 'composer.lock' \
	--exclude 'package.json' \
	--exclude 'package-lock.json' \
	--exclude 'phpcs.xml' \
	--exclude 'phpcs.xml.dist' \
	--exclude 'phpunit.xml' \
	--exclude 'phpunit.xml.dist' \
	--exclude 'Thumbs.db' \
	--exclude 'desktop.ini' \
	"$ROOT/" "$PLUGIN_STAGE/"

hidden="$(find "$PLUGIN_STAGE" -name '.*' -print)"
if [[ -n "$hidden" ]]; then
	printf '%s\n' "$hidden" >&2
	fail "hidden files survived staging"
fi

required=(
	musomo-quote.php
	readme.txt
	uninstall.php
	LICENSE
	languages/index.php
	languages/musomo-quote.pot
	languages/musomo-quote-it_IT.mo
	languages/musomo-quote-it_IT.po
	languages/musomo-quote-de_DE.mo
	languages/musomo-quote-de_DE.po
	languages/musomo-quote-fr_FR.mo
	languages/musomo-quote-fr_FR.po
	languages/musomo-quote-es_ES.mo
	languages/musomo-quote-es_ES.po
)

for relative in "${required[@]}"; do
	if [[ ! -f "$PLUGIN_STAGE/$relative" ]]; then
		fail "required package file missing: $relative"
	fi
done

shopt -s nullglob
mo_files=("$PLUGIN_STAGE"/languages/*.mo)
if [[ "${#mo_files[@]}" -eq 0 ]]; then
	fail "no bundled .mo catalogs were staged"
fi

for mo in "${mo_files[@]}"; do
	magic="$(od -An -t x1 -N 4 "$mo" | tr -d '[:space:]')"
	case "$magic" in
		de120495|950412de) ;;
		*) fail "invalid gettext catalog: ${mo#"$PLUGIN_STAGE/"} ($magic)" ;;
	esac
done
shopt -u nullglob

rm -f "$OUT"
(
	cd "$STAGE"
	COPYFILE_DISABLE=1 zip -r -X -q "$OUT" musomo-quote
)

listing="$(unzip -Z1 "$OUT")"
audit_failed=0
while IFS= read -r entry; do
	[[ -z "$entry" ]] && continue
	case "$entry" in
		musomo-quote|musomo-quote/*) ;;
		*)
			echo "unexpected archive path: $entry" >&2
			audit_failed=1
			;;
	esac
	case "$entry" in
		__MACOSX/*|*/__MACOSX/*|*/.DS_Store|.DS_Store|*/.gitignore|.gitignore|*/.git/*|.git/*|*/.github/*|.github/*)
			echo "development file in archive: $entry" >&2
			audit_failed=1
			;;
	esac
	IFS='/' read -r -a parts <<< "$entry"
	for part in "${parts[@]}"; do
		case "$part" in
			.*)
				echo "hidden path in archive: $entry" >&2
				audit_failed=1
				;;
		esac
	done
done <<< "$listing"

if [[ "$audit_failed" -ne 0 ]]; then
	fail "archive audit failed"
fi

for relative in "${required[@]}"; do
	case $'\n'"$listing"$'\n' in
		*$'\n'"musomo-quote/$relative"$'\n'*) ;;
		*) fail "archive is missing musomo-quote/$relative" ;;
	esac
done

echo "Built $OUT"
echo "Version $VERSION"
unzip -Z1 "$OUT"
