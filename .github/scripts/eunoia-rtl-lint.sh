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

# Tailwind physical utilities, optionally behind variants (md:, hover:, rtl:, !important …).
d='[:space:]"'"'"'`'
cls="(^|[${d}:!])(-?(ml|mr|pl|pr|left|right|scroll-m[lr]|scroll-p[lr])-[^${d}]+|(border-[lr]|rounded-[lr]|rounded-[tb][lr]|float-(left|right)|clear-(left|right)|text-(left|right))(-[^${d}]*)?)([${d}]|\$)"
# Physical CSS properties in stylesheets / style="" attributes.
css='(margin|padding|border)-(left|right)[[:space:]]*:|text-align[[:space:]]*:[[:space:]]*(left|right)|float[[:space:]]*:[[:space:]]*(left|right)|(^|[;{[:space:]"])(left|right)[[:space:]]*:'

status=0
run_grep() { # pattern, includes...
  local pattern=$1; shift
  local out rc=0
  out=$(grep -rnEI "$@" -e "$pattern" "${existing[@]}") || rc=$?
  if [ $rc -gt 1 ]; then echo "RTL lint: grep error ($rc)"; exit 2; fi
  out=$(printf '%s\n' "$out" | grep -v 'rtl-lint: ignore' | grep -v '^$' || true)
  if [ -n "$out" ]; then echo "$out"; status=1; fi
}

echo "Checking for physical direction classes / CSS properties…"
run_grep "$cls" --include='*.php' --include='*.vue' --include='*.js' --include='*.ts' --include='*.html' --include='*.css'
run_grep "$css" --include='*.blade.php' --include='*.vue' --include='*.html' --include='*.css'

if [ $status -ne 0 ]; then
  echo "RTL lint failed — use logical ms-/me-/ps-/pe-/start-/end-/text-start/text-end, border-s/e, rounded-s/e, float-start/end, margin-inline-* / padding-inline-* / inset-inline-*. Mark a deliberate exception with 'rtl-lint: ignore'."
  exit 1
fi

echo "RTL lint passed (${existing[*]})"
