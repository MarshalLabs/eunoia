# PATCHES.md — changes to upstream AureusERP files

Every change outside `plugins/eunoia/**` and `docs/eunoia/**` must be listed here. Keep this list short (target ≤ 10).

| ID | Status | Files | Why | Upstream PR |
|---|---|---|---|---|
| P1 | Ready (tested locally 17 Oct 2026 on PostgreSQL 16) | New migration `2026_10_17_000001_widen_currencies_rounding_precision.php` (in `patches/P1-currency-rounding/`; upstream target: `plugins/webkul/support/database/migrations/` + register in `SupportServiceProvider`) | `currencies.rounding` was `decimal(8,2)` → JOD rounding 0.001 saved as 0.00 → POS till float helpers throw. After patch: JOD/KWD/BHD/OMR/TND/IQD/LYD = 0.001, 0 currencies with rounding ≤ 0 | _to open_ |
| P2 | Planned | `resources/css/app.css` | Remove RTL overrides that double-flip flex rows and swap margins | _to open_ |
| P3 | Planned | `plugins/webkul/security/src/Data/states.json` | Add Palestinian governorates | _to open_ |
| P4 | Planned | `AGENTS.md` (replaced), `CLAUDE.md` (new) | Upstream file had wrong framework versions | No (ours) |
| P6 | Done (E0-03) | `docker-compose.eunoia.yml`, `docker/eunoia/**` (new files, ours) | Local dev stack on PHP 8.4; upstream files untouched | No (ours) |
| P7 | Done (E0-04) | `.github/workflows/eunoia_ci.yml`, `.github/scripts/eunoia-*`, `.gitleaks.toml` (new files, ours) | CI gates for plugins/eunoia | No (ours) |
| P5 | Spike (E3-03) | POS till | Extension point for extra till components, only if none exists | Discuss upstream first |

## Template
```
| Pn | Status | path/to/file | reason | link |
```
