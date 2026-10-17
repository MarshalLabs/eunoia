# AGENTS.md — Eunoia (AureusERP fork for Jordan & Palestine)

This file is for AI coding agents (Claude Code, Codex, Cursor…). Read it fully before changing anything.
Humans: see `docs/eunoia/00-README.md`.

## 1. What this repo is
- **AureusERP** (Webkul, MIT) is the engine: `plugins/webkul/*` — **upstream code, do not edit**.
- **Eunoia** is our product: `plugins/eunoia/*` — Jordan/Palestine localization, JoFotara e-invoicing, Arabic UX, perfume-shop POS, WhatsApp, loyalty, owner app.
- Product spec: `docs/eunoia/02-target/04-product-requirements.md` (requirement IDs like `JF-05`).
- Architecture: `docs/eunoia/02-target/06-architecture.md`. Design system: `docs/eunoia/02-target/05-design-system.md`.
- Work plan: `docs/eunoia/04-delivery/08-implementation-roadmap.md` (ticket IDs like `E1-03`).

## 2. Real versions (upstream's old AGENTS.md was wrong — trust this list or `composer.lock`)
PHP 8.3+ · Laravel 13 · FilamentPHP 5 · Livewire 4 · Tailwind CSS 4 · Vue 3.5 (POS till only) · Pest 4 · PostgreSQL 16 (our default) / MySQL 8 · Node 22.
When unsure about an API, check the installed source in `vendor/` (e.g. `vendor/filament/filament/src`) instead of guessing from older versions. If Laravel Boost MCP is available, use its `search-docs` tool.

## 3. Hard rules
1. **Never edit `plugins/webkul/**`, `app/`, `config/`, `resources/`, root `composer.json`** unless the task says "upstream patch". If a patch is needed: stop, explain why, and add an entry to `PATCHES.md` (file, reason, link to upstream issue/PR).
2. **All new code goes in `plugins/eunoia/<package>/`**, following the structure of `plugins/webkul/payments` (smallest example): `composer.json`, `src/<Name>ServiceProvider.php` extending `Webkul\PluginManager\PackageServiceProvider`, `src/<Name>Plugin.php` implementing `Filament\Contracts\Plugin`, `database/`, `resources/lang/{ar,en}`, `tests/`.
3. **Namespaces:** `Eunoia\<Package>\…` (e.g. `Eunoia\JoFotara\Services\UblInvoiceBuilder`). Translation namespace: `eunoia-<package>` (e.g. `__('eunoia-jofotara::documents.status.accepted')`).
4. **Tables** are prefixed `eunoia_`. Never alter upstream columns (exception: patch P1 currency precision). Add, don't change.
5. **Money:** use upstream helpers (`money()`, `float_round()`, `float_compare()` with the currency's rounding). JOD has 3 decimals. Never use floats for comparisons without them. Never round to 2 decimals by assumption.
6. **Arabic first:** every user-facing string goes through `__()` with **both `ar` and `en`** keys. Follow the glossary in the design system §7.2 (e.g. عميل not شريك, ضريبة المبيعات العامة not VAT for Jordan). No English plurals on Arabic labels — set `pluralModelLabel` explicitly.
7. **RTL:** only logical Tailwind classes (`ms-/me-/ps-/pe-/start-/end-/text-start/text-end`). Never `ml-/mr-/pl-/pr-/left-/right-/text-left/text-right`. Never flip flex direction for RTL by hand.
8. **Design tokens only** (`plugins/eunoia/theme`). No raw hex colours in Eunoia views.
9. **Secrets:** never log, print, commit or put in tests: JoFotara `Client-Id`/`Secret-Key`, WhatsApp tokens, gateway keys. Use `encrypted` casts and settings classes. Tests use fake values.
10. **JoFotara:** never send to the live API from dev/CI. Use the mock (`JOFOTARA_BASE_URL=http://127.0.0.1:8080`, started with `npx jofotara-kit serve --port 8080`). Never generate a QR code yourself — only display the one JoFotara returns. Validate generated XML with `npx jofotara-kit validate <file>`.
11. **Don't change dependencies** (composer/npm) without saying so in the PR description and why.
12. **Don't uninstall plugins** (`*:uninstall` drops tables).
13. Upstream conventions apply inside our code too: explicit return types, constructor property promotion, curly braces always, Enum keys in TitleCase, Eloquent relationships over raw `DB::`, eager loading to avoid N+1, PHPDoc instead of inline comments.

## 4. Commands
```bash
# setup (first time) — see docs/eunoia/04-delivery/09-local-setup-runbook.md
composer install && npm ci && npm run build
php artisan erp:install --country=JO --no-interaction --admin-email=admin@example.com --admin-password=admin123
php artisan point-of-sale:install --no-interaction
php artisan eunoia:install --country=JO        # once eunoia/core exists

# daily
composer run dev                                # server + queue + logs + vite
npx jofotara-kit serve --port 8080              # JoFotara mock (separate terminal)

# quality (run before you say "done")
vendor/bin/pint --dirty
vendor/bin/pest plugins/eunoia                  # our tests
vendor/bin/pest plugins/webkul/<touched-plugin> # upstream tests for anything you touched
php artisan eunoia:lint-rtl                     # once available
```
Run tests and migrations **only against the local dev database**, never against staging/production.

## 5. How to work on a ticket
1. Read the ticket in the roadmap + the requirement IDs it references.
2. Read the relevant upstream code first (models, services, events). Prefer events/observers/macros over patches. Useful hooks: `Webkul\Account\Events\{MoveConfirmed,MovePaid,MoveCancelled,MoveReversed}`, `Webkul\PointOfSale\Events\{OrderPaid,OrderRefunded,SessionClosed}`.
3. Write a short plan (files to add/change, tables, tests) in the PR description or as a comment before coding big changes.
4. Write tests first for business logic (Pest). For JoFotara XML, add a golden file in `tests/Fixtures/` and validate it with the kit.
5. Implement. Keep PRs small (≤ ~400 changed lines when possible).
6. Run the quality commands. Attach AR + EN screenshots (390 px and 1280 px) for UI changes.
7. Update docs if behaviour or a decision changed (`docs/eunoia/...`).

## 6. Definition of done
- [ ] Code only in `plugins/eunoia/**` (or a listed patch in `PATCHES.md`)
- [ ] `ar` + `en` translations, glossary respected
- [ ] Tests added and passing; Pint clean
- [ ] No secrets, no live API calls, no physical RTL classes
- [ ] Screenshots for UI; JoFotara XML validated for e-invoice changes
- [ ] Requirement/ticket IDs in commit message, e.g. `feat(jofotara): queue documents on MoveConfirmed [JF-05][E1-07]`

## 7. When to stop and ask the human
- Anything that changes tax calculations, chart of accounts, or JoFotara mapping (needs accountant/ISTD confirmation).
- A needed upstream patch.
- A new dependency or external service.
- Requirements that conflict with each other or with upstream behaviour.
- Anything touching production data or real customer phone numbers.
