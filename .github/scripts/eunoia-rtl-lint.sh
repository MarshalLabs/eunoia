#!/usr/bin/env bash
# Eunoia RTL lint (E0-04, design system §4): fails on physical direction classes
# in plugins/eunoia views/scripts. Use ms-/me-/ps-/pe-/start-/end-/text-start/text-end.
# Usage: .github/scripts/eunoia-rtl-lint.sh [path ...]   (default: plugins/eunoia)
set -euo pipefail

paths=("$@")
[ ${#paths[@]} -eq 0 ] && paths=(plugins/eunoia)

existing=()
for p in "${paths[@]}"; do [ -e "$p" ] && existing+=("$p"); done
if [ ${#existing[@]} -eq 0 ]; then
  echo "RTL lint: nothing to check (${paths[*]} not found)"
  exit 0
fi

# Tailwind physical utilities, optionally behind variants (md:, hover:, rtl: …) and a leading "-".
pattern='(^|[[:space:]"'"'"'`:])-?(ml|mr|pl|pr|left|right|border-l|border-r|rounded-l|rounded-r|rounded-tl|rounded-tr|rounded-bl|rounded-br|scroll-ml|scroll-mr|scroll-pl|scroll-pr)-([0-9]|px|auto|full|\[)|(^|[[:space:]"'"'"'`:])text-(left|right)([[:space:]"'"'"'`]|$)'

if matches=$(grep -rnEI --include='*.blade.php' --include='*.vue' --include='*.js' --include='*.ts' --include='*.html' --include='*.css' \
    -e "$pattern" "${existing[@]}" | grep -v 'rtl-lint: ignore'); then
  echo "RTL lint failed — physical direction classes found (use logical ms-/me-/ps-/pe-/start-/end-/text-start/text-end):"
  echo "$matches"
  exit 1
fi

echo "RTL lint passed (${existing[*]})"
