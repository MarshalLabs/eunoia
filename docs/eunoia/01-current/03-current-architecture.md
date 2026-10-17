# 03 — Current Architecture (AureusERP baseline)

> **What this is:** how AureusERP is built today, from reading the code. Paths are relative to the repo root.
>
> **Date:** 17 Oct 2026 · **Upstream commit:** `44862a7` (16 Oct 2026)

---

## 1. High-level picture

```
                 ┌───────────────────────── Laravel 13 app ─────────────────────────┐
 Browser ──────► │  Filament panels                                                 │
  /admin         │   ├─ AdminPanelProvider     (back office, Shield RBAC, MFA)      │
  /pos           │   ├─ PosPanelProvider       (SPA shell → Vue 3 till, PWA)        │
  /              │   └─ CustomerPanelProvider  (portal + website, guard=customer)   │
 Mobile/API ───► │  routes/api.php + plugins/*/routes/api.php (Sanctum, /admin/api/v1)│
                 │                                                                  │
                 │  plugins/webkul/*  (29 packages, merged by composer-merge-plugin)│
                 │   each = ServiceProvider + FilamentPlugin + migrations + seeders │
                 │          + Models + Services + Events + Policies + lang + views  │
                 └──────────────┬───────────────────┬──────────────────┬────────────┘
                                │                   │                  │
                         MySQL 8 / PostgreSQL   Queue (database)   Storage (public disk:
                         (db_dialect())         + scheduler        PDFs, uploads)
```

## 2. Repository layout

| Path | Purpose |
|---|---|
| `app/Providers/Filament/*PanelProvider.php` | The three panels |
| `app/Http/Middleware/SetLocale.php` | Locale resolution (query → session → user → default) |
| `app/Http/Middleware/ApplyBrandSettings.php` | Runtime colour palette from settings |
| `config/app.php` | `supported_locales` (with `rtl` flag), currency default |
| `plugins/webkul/<name>/` | Each module as a Composer package (namespace `Webkul\<Name>\`) |
| `plugins/webkul/support/src/helpers.php` | Global helpers: `settings()`, `money()`, `default_currency_code()`, `db_dialect()`, `prepare_rtl_html()` |
| `plugins/webkul/security/src/Data/*.json` | Seed data: countries, currencies, states |
| `lang/`, `plugins/*/resources/lang/{en,ar,es,fr,pt_BR}` | Translations |
| `docker/production`, `docker-compose.yml` | Sail dev stack (PHP 8.4, MySQL 8, Redis, Mailpit) + production image |
| `.github/workflows` | `pest_tests`, `playwright_tests`, `playwright_report`, `translations_check`, `docker_publish` |
| `AGENTS.md` | Laravel Boost guidelines for coding agents (**stale:** says Laravel 11 / Filament 4 / Livewire 3; composer is Laravel 13 / Filament 5 / Livewire 4) |

## 3. The plugin system (most important part for us)

### 3.1 How a plugin is loaded
1. Root `composer.json` → `extra.merge-plugin.include: ["plugins/*/*/composer.json"]`. **Any folder `plugins/<vendor>/<name>/` with a `composer.json` is merged automatically.** So we can add `plugins/eunoia/*` without editing upstream files.
2. The plugin's `composer.json` registers a Laravel service provider via `extra.laravel.providers`.
3. The provider extends `Webkul\PluginManager\PackageServiceProvider` and declares, in `configureCustomPackage(Package $package)`:
   - `->hasTranslations()`, `->hasMigrations([...])`, `->runsMigrations()`
   - `->hasDependencies(['accounts', ...])`
   - `->hasSeeder(...)`, `->hasInstallCommand(...)`, `->hasUninstallCommand(...)`
4. `packageRegistered()` registers a Filament `Plugin` on every panel via `Panel::configureUsing(...)`.
5. The Filament plugin checks `Package::isPluginInstalled($id)` and then `discoverResources / Pages / Clusters / Widgets` for the right panel id.

### 3.2 Install lifecycle
```
php artisan erp:install          # core + admin user
php artisan <plugin>:install     # migrations + seeders + dependencies
php artisan <plugin>:uninstall   # drops the plugin's tables (data loss!)
```

### 3.3 Dependency graph (main business plugins)
Read from each provider's `hasDependencies([...])`:

| Plugin | Declares dependency on |
|---|---|
| `products` | — |
| `accounts` | products |
| `invoices` | accounts |
| `accounting` | accounts |
| `payments` | accounts |
| `inventories` | products |
| `sales` | invoices, payments |
| `purchases` | invoices |
| `manufacturing` | products, inventories |
| `point-of-sale` | products, inventories, invoices |

```
products ─► accounts ─► invoices ─► sales (+ payments) / purchases / point-of-sale (+ inventories)
   │            ├─► accounting (reports)
   │            └─► payments
   └─► inventories ─► manufacturing / point-of-sale
```

## 4. Domain model essentials

### 4.1 Accounting (Odoo-style)
- `accounts_account_moves` = invoices, bills, credit notes, journal entries (one table, `move_type`).
- Lines in `accounts_account_move_lines`; money columns are `decimal(15,4)`.
- `MoveWorkflow` service: `post()` → sets `POSTED`, recomputes, dispatches **`MoveConfirmed`**; `resetToDraft()` is allowed for posted/cancelled moves.
- Taxes: `accounts_taxes` with repartition lines, tax groups, `TaxManager`; fiscal positions map taxes.
- Enums worth knowing: `MoveState`, `MoveType`, `PaymentState`, `InvoiceFormat` (FacturX, UBL BIS3, XRechnung, NLCIUS, A-NZ, SG — **labels only, no generator code**), `InvoiceSendingMethod` (`manual`, `email`, `snailmail`), `PartyIdentificationScheme` (EU/Peppol IDs).

### 4.2 Currencies
- Table `currencies`: `name` (ISO code), `symbol`, `iso_numeric`, `decimal_places`, `rounding` **`decimal(8,2)`**, `active`.
- Seed (`currencies.json`): JOD has `decimal_places=3`, `rounding=0.001`, but the column has only 2 decimals → **0.001 is stored as 0.00** (bug). JOD and ILS are seeded **inactive**; default is USD.
- `money()` uses PHP `intl` (`Number::currency`); in RTL locales it forces `en`.

### 4.3 Countries / states
- Jordan (`JO`, phone 962, currency JOD) with 12 governorates.
- Palestine (`PS`, "State of Palestine", phone 970, currency **ILS only**) with **0 governorates**.
- Both have `zip_required=true`, which is unusual for local addresses.
- Country/state names are English only.

### 4.4 Partners
Single `partners_partners` table (customers, vendors, contacts) with `tax_id`, `company_registry`, `phone`, `mobile`. No national-ID type, no TIN validation, no WhatsApp opt-in.

### 4.5 POS
- Models: Config, Session, Order, OrderLine, Payment, PaymentMethod, CashMovement, Floor/Table, Printer, Bill …
- ~40 services (`OrderProcessor`, `ReceiptBuilder`, `SessionCloser`, `PosInvoicer`, `OfflinePartnerResolver` …).
- Front end: Vue 3 till (`resources/js/till`, 47 components), IndexedDB offline store, PWA install, Epson ePOS printers.
- Upstream is actively moving the whole POS into one Vue app (`docs/superpowers/plans/2026-10-09-pos-fully-vuejs.md`).

## 5. Cross-cutting concerns

| Concern | Implementation |
|---|---|
| Auth | Filament auth, guards `web` + `customer`, Sanctum for API, MFA (TOTP) |
| Authorization | `bezhansalleh/filament-shield` + Spatie permission; Policies per model; company scoping (`CompanyContext`) |
| i18n | Laravel translations, `spatie/laravel-translatable` for model fields, `lara-zeus/spatie-translatable` in Filament |
| RTL | `HasRtlSupport` trait (JS sets `dir`, CSS overrides), `prepare_rtl_html()` for PDFs |
| PDF | `barryvdh/laravel-dompdf`, `PDFHandler` trait, DejaVu Sans, `ar-php` for Arabic glyph shaping |
| Barcodes/QR | `milon/barcode`, `endroid/qr-code`, `chillerlan/php-qrcode`, `bacon/bacon-qr-code` (already installed — useful for JoFotara QR) |
| Excel | `maatwebsite/excel` |
| Settings | `spatie/laravel-settings` (`BrandSettings`, `CurrencySettings`, …) |
| API docs | `knuckleswtf/scribe` |
| Queue / jobs | `database` driver, `php artisan queue:listen` in `composer dev` |
| Mail | `log` mailer by default |
| DB portability | `db_dialect()` → `MySqlDialect` / `PostgresDialect` |
| Tests | Pest 4 (+ Playwright e2e). Upstream agent rule: agents must not run migrations/tests themselves |
| Dev tooling | Laravel Boost MCP (docs search, tinker, DB schema), Pint |

## 6. Deployment model today

- **Single tenant per install.** Multi-company inside one database (`company_id` on records).
- Dev: Sail (Docker) or `php artisan serve` + `composer dev` (server, queue, logs, vite in parallel).
- Prod: `docker/production` image published by CI; upstream also sells managed "AureusERP Cloud Hosting".

## 7. Extension points we can use without touching core

| Need | Hook |
|---|---|
| Add modules | New package under `plugins/eunoia/*` |
| React to invoice posting | Listen to `Webkul\Account\Events\MoveConfirmed` (also `MovePaid`, `MoveCancelled`, `MoveReversed`) |
| Add fields to existing models | Own migration + `fields` plugin, or `Model::resolveRelationUsing` / macros |
| Add actions/columns to existing Filament resources | `Panel::configureUsing`, resource extension via service container bindings, render hooks |
| Change theme | `BrandSettings` + our own Filament theme CSS + render hooks |
| Add translations / override strings | Publish `lang/vendor/<plugin>/ar/...` overrides |
| Block an action (e.g. reset to draft after e-invoice submission) | Eloquent `updating` observer on the move model, or a policy override |

## 8. Risks in the current architecture (for our use)

1. **Upstream moves fast** (3,700+ commits, big refactors like POS → Vue). A hard fork would rot within months.
2. **No tenancy** — SaaS needs either instance-per-customer or a tenancy layer.
3. **Arabic/RTL is a retrofit**, so UI changes upstream can silently break RTL.
4. **The stale `AGENTS.md`** will mislead AI coding agents about versions.
5. **Plugin uninstall drops tables** — dangerous in production; must be locked down.
