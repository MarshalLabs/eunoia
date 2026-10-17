# PATCHES.md — changes to upstream AureusERP files

Every change outside `plugins/eunoia/**` and `docs/eunoia/**` must be listed here. Keep this list short (target ≤ 10).

| ID | Status | Files | Why | Upstream PR |
|---|---|---|---|---|
| P1 | **Applied (E0-06, 17 Oct 2026)** | `plugins/webkul/support/database/migrations/2026_10_17_000001_widen_currencies_rounding_precision.php` (new, copy of `patches/P1-currency-rounding/`) + 1 line in `plugins/webkul/support/src/SupportServiceProvider.php` (`hasMigrations`) | `currencies.rounding` was `decimal(8,2)` → JOD rounding 0.001 saved as 0.00 → POS till float helpers throw (F1). Now `decimal(12,6)`; data fix restores seed rounding for BHD, CLF, IQD, JOD, KWD, LYD, OMR, TND, UYI, UYW; 0 currencies with rounding ≤ 0 (SQL in progress log). Tests: `plugins/eunoia/core/tests/Feature/CurrencyPrecisionPatchTest.php` | _to open_ |
| P2 | Not needed for panels (E1-06) | `resources/css/app.css` | Checked 17 Oct: `app.css` is not loaded in the Filament panels (only the public website/till Vite entry). The admin double-flips came from upstream's `support::rtl.styles` view, which `eunoia/theme` overrides via `View::prependNamespace` — no upstream edit. Revisit for the customer portal/website | — |
| P3 | Planned | `plugins/webkul/security/src/Data/states.json` | Add Palestinian governorates | _to open_ |
| P4 | Planned | `AGENTS.md` (replaced), `CLAUDE.md` (new) | Upstream file had wrong framework versions | No (ours) |
| P6 | Done (E0-03) | `docker-compose.eunoia.yml`, `docker/eunoia/**` (new files, ours) | Local dev stack on PHP 8.4; upstream files untouched | No (ours) |
| P7 | Done (E0-04) | `.github/workflows/eunoia_ci.yml`, `.github/scripts/eunoia-*`, `.gitleaks.toml` (new files, ours) | CI gates for plugins/eunoia | No (ours) |
| P8 | Done (E0-05) | `bootstrap/providers.php` (+1 line: `Eunoia\Core\EunoiaServiceProvider::class`) | Upstream registers providers by hand; composer's merge plugin only merges autoload. The aggregator registers every `plugins/eunoia/*/composer.json` → `extra.laravel.providers`, so new Eunoia plugins need no further core edits. Architecture §2 corrected | No (ours) |
| P9 | Planned (E1-03 gap) | ~25 upstream resources/clusters/pages without Arabic labels (`Customers`, `Vendors`, `Configurations`, `Accounts`, `Invoices` in accounts…) | Filament derives these labels from English class names (no `__()`), and the static label properties are shared through Filament base classes, so a plugin cannot set them per resource. Needs `getModelLabel()`/`getNavigationLabel()` with translations upstream (F11) | Open upstream |
| P5 | Spike (E3-03) | POS till | Extension point for extra till components, only if none exists | Discuss upstream first |

## Template
```
| Pn | Status | path/to/file | reason | link |
```
