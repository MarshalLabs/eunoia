# 06 — Eunoia Target Architecture

> **Rule #1: AureusERP is the engine, Eunoia is a set of plugins.** We never hard-fork. Upstream code stays as untouched as possible so we can pull updates every month.
>
> **Date:** 17 Oct 2026 · **Baseline:** `01-current/03-current-architecture.md`

---

## 1. Context

```
                        ┌────────────────────── Eunoia (one instance per shop) ──────────────────────┐
  Owner (phone) ──────► │ /owner  ← NEW panel (eunoia/owner)                                        │
  Cashier (tablet) ───► │ /pos    ← upstream POS + eunoia/perfumery + eunoia/theme                  │
  Accountant (PC) ────► │ /admin  ← upstream back office + eunoia/* resources                       │
  Customer ───────────► │ /       ← customer portal (points, invoices, blends)                      │
                        │                                                                           │
                        │ plugins/webkul/*  (upstream, untouched)                                    │
                        │ plugins/eunoia/*  (ours)                                                   │
                        └───────┬───────────────┬────────────────┬───────────────┬──────────────────┘
                                │               │                │               │
                        PostgreSQL 16     Redis (queue,     JoFotara API     WhatsApp Cloud API / SMS
                        (or MySQL 8)      cache, locks)     (ISTD)           Payment gateway (R3+)
```

## 2. Repository strategy

```
github.com/<you>/eunoia            ← our repo (a fork of aureuserp/aureuserp)
  remotes:  origin   = our repo
            upstream = https://github.com/aureuserp/aureuserp
  branches: main            (deployable)
            upstream-sync   (monthly merge of upstream/master, tested before merge to main)
            feat/*          (work)
```

| Folder | Owner | Rule |
|---|---|---|
| `plugins/webkul/**` | Upstream | Don't edit. If you must, record it in `PATCHES.md` (file, reason, upstream PR link) |
| `plugins/eunoia/**` | Us | All our code |
| `app/`, `config/` | Upstream | Only additive config (new keys); edits go in `PATCHES.md` |
| `docs/eunoia/**` | Us | These documents |
| `AGENTS.md`, `CLAUDE.md` | Us | Agent guide (we replace upstream's stale Boost file; keep Boost content in `docs/eunoia/agents/laravel-boost.md`) |

Root `composer.json` already merges `plugins/*/*/composer.json`, so `plugins/eunoia/<name>/composer.json` is picked up automatically — **no core change needed to add plugins**.

Target: **≤ 10 patched upstream files**, each with an upstream PR.

## 3. Eunoia plugins

| Package (folder `plugins/eunoia/…`) | Namespace | Depends on | Release | Purpose |
|---|---|---|---|---|
| `core` | `Eunoia\Core` | support, security | R0 | Shared helpers: `TenantSettings`, phone normalizer, feature flags, audit log, `eunoia:doctor` command |
| `theme` | `Eunoia\Theme` | core | R0 | Tokens, fonts, Filament themes per panel, Blade components, RTL fixes, lint config |
| `arabic` | `Eunoia\Arabic` | core | R0 | Glossary-based string overrides, `pluralModelLabel` fixes, amount-in-words (`ar-php`), Levantine month names, date/number formatting |
| `localization-jo` | `Eunoia\LocalizationJo` | accounts, invoices | R0 | JOD 3-dp fix, JO taxes (16/reduced/0/exempt), chart of accounts, governorates AR, partner identity fields, address rules |
| `jofotara` | `Eunoia\JoFotara` | localization-jo, invoices | R1 | UBL 2.1 builder, API client, outbox + jobs, QR, locking, dashboard, mock mode |
| `documents` | `Eunoia\Documents` | arabic, theme | R1 | A4 invoice, credit note, 80 mm receipt templates; PDF renderer abstraction |
| `perfumery` | `Eunoia\Perfumery` | point-of-sale, inventories | R1 | Bottle-size variants, blend recipes (BoM-light), saved blends, testers, POS components |
| `messaging` | `Eunoia\Messaging` | core, partners | R1 | WhatsApp Cloud API + SMS channels, templates, consent, delivery webhooks, `InvoiceSendingMethod` extension |
| `loyalty` | `Eunoia\Loyalty` | point-of-sale, partners | R2 | Points ledger, rules, tiers, POS earn/redeem |
| `feedback` | `Eunoia\Feedback` | messaging | R2 | Review/NPS requests, responses, dashboard |
| `campaigns` | `Eunoia\Campaigns` | messaging, loyalty | R2 | Segments (inactive 60 d, VIP…), scheduled WhatsApp campaigns |
| `owner` | `Eunoia\Owner` | all above | R2 | New `/owner` Filament panel (mobile-first PWA) |
| `payments-local` | `Eunoia\PaymentsLocal` | payments | R3 | CliQ (manual ref + reconcile), gateway links (PayTabs/HyperPay adapter) |
| `saas` | `Eunoia\Saas` | core | R3 | Onboarding wizard, plan limits, subscription status, provisioning hooks |
| `localization-ps` | `Eunoia\LocalizationPs` | accounts | R4 | VAT 16 %, ILS/JOD/USD, governorates, multi-currency drawer |
| `payroll-jo` | `Eunoia\PayrollJo` | employees | R4 | Payslips, Social Security, income tax |

Each package follows the upstream pattern exactly (copy `plugins/webkul/payments` as the smallest template):

```
plugins/eunoia/jofotara/
├── composer.json                 # name eunoia/jofotara, PSR-4 Eunoia\JoFotara\ → src/, provider
├── database/{migrations,seeders,factories}/
├── resources/{lang/{ar,en},views,js,css}/
├── routes/{web.php,api.php}
├── src/
│   ├── JoFotaraServiceProvider.php   # extends Webkul\PluginManager\PackageServiceProvider
│   ├── JoFotaraPlugin.php            # implements Filament\Contracts\Plugin
│   ├── Contracts/ Models/ Services/ Jobs/ Listeners/ Events/ Enums/ Settings/ Filament/ Http/
└── tests/{Unit,Feature}/
```

Install order: `php artisan eunoia-core:install` → `eunoia-theme:install` → … (each declares `hasDependencies`). A meta command `php artisan eunoia:install --country=JO` installs the right set.

## 4. Data model additions

All new tables are prefixed `eunoia_` and only **add** to upstream tables via separate tables or nullable columns (never alter upstream columns, except the currency precision fix).

### 4.1 Localization / identity
| Table / change | Columns (main) |
|---|---|
| `currencies.rounding` (**patch**) | `decimal(8,2)` → `decimal(12,6)`; data fix JOD = 0.001 (also PR upstream) |
| `eunoia_partner_identities` | `partner_id`, `type` (`tin`,`nin`,`passport`,`cr`), `value`, `country_code`, `verified_at` |
| `eunoia_state_translations` or translatable `name` | Arabic names for JO/PS governorates |

### 4.2 JoFotara
| Table | Columns (main) |
|---|---|
| `eunoia_jofotara_settings` (spatie settings) | `client_id`, `secret_key` (encrypted), `tin`, `income_source_sequence`, `base_url`, `mode` (`mock`,`live`), `auto_submit` |
| `eunoia_jofotara_documents` | `id`, `company_id`, `move_id` (unique), `uuid`, `icv` (unique per company), `type_code` (e.g. `012`), `doc_type` (`388`,`381`), `status` (`queued`,`sending`,`accepted`,`rejected`,`failed`), `qr_payload`, `attempts`, `last_error`, `submitted_at`, `accepted_at` |
| `eunoia_jofotara_attempts` | `document_id`, `request_xml` (compressed), `response_json`, `http_status`, `duration_ms`, `created_at` |
| `eunoia_jofotara_counters` | `company_id`, `next_icv` — updated with `SELECT … FOR UPDATE` |

### 4.3 CX
| Table | Purpose |
|---|---|
| `eunoia_consents` | `partner_id`, `channel` (`whatsapp`,`sms`,`email`), `purpose` (`transactional`,`marketing`), `granted`, `source`, `at` |
| `eunoia_messages` | Outbound/inbound messages, template, status, provider id, related model (morph) |
| `eunoia_loyalty_programs` / `eunoia_loyalty_rules` / `eunoia_loyalty_ledger` | Points rules and an append-only ledger (`partner_id`, `delta`, `reason`, `order_id`, `expires_at`) — balance = SUM |
| `eunoia_feedback_requests` / `eunoia_feedback_responses` | NPS/rating per order |
| `eunoia_blends` / `eunoia_blend_lines` | Saved customer blends: product (oil), quantity, unit; linked to partner |
| `eunoia_audit_log` | Who changed price/discount/refund/settings, before/after |

## 5. Key flows

### 5.1 Invoice → JoFotara (outbox pattern)
```
Cashier/accountant posts invoice
  └─ MoveWorkflow::post()  (upstream)
       └─ event MoveConfirmed($move)
            └─ Eunoia\JoFotara\Listeners\QueueDocument            (sync, in same request)
                 ├─ skip if company not JO / mode off / move not invoice|refund
                 ├─ DB transaction: lock counter → assign ICV + UUID → insert jofotara_documents(status=queued)
                 └─ dispatch SubmitDocument job (afterCommit)
SubmitDocument job (queue "jofotara", tries=10, backoff 1m→6h, unique per document)
  ├─ UblInvoiceBuilder::build($document)  → XML (UBL 2.1)
  ├─ (dev/CI) JoFotaraKitValidator::validate($xml) → fail fast on rule errors
  ├─ JoFotaraClient::submit(base64(xml))  POST {base_url}/core/invoices/  headers Client-Id, Secret-Key
  ├─ parse response → accepted: store QR + status | rejected: store reasons, notify accountant
  └─ record attempt (request/response) — secrets never stored
Lock: Observer on Move `updating` → if jofotara document status in (sending, accepted) → block changes/reset to draft
Correction: credit note (381) with BillingReference → original number + UUID + reason → same pipeline
```

Offline POS: the till already queues orders in IndexedDB. When they sync, the server posts the invoice → same listener. Receipt printed offline shows "سيتم الإبلاغ عن الفاتورة" and a short link; the QR version is sent by WhatsApp after acceptance.

### 5.2 Receipt → WhatsApp
`Webkul\PointOfSale\Events\OrderPaid` (POS) / `Webkul\Account\Events\MoveConfirmed` (invoices) → if partner has WhatsApp consent → `SendTemplateMessage` job → `WhatsAppCloudChannel` → store `eunoia_messages` → webhook updates status (sent/delivered/read/failed) → fallback SMS on failure (configurable).

### 5.3 Loyalty
`OrderPaid` → `AwardPoints` (and `OrderRefunded` → reverse points) (rules: points per JOD, category multipliers, tier bonus) → ledger insert. Redeem in POS → creates a discount line + negative ledger row in the **same** transaction as the order.

## 6. Technical decisions (ADR summary)

| # | Decision | Why | Alternatives |
|---|---|---|---|
| ADR-01 | Plugins, not a hard fork | Keep upstream updates; MIT allows it | Hard fork (rejected: rot) |
| ADR-02 | **PostgreSQL 16** as our default DB | Supported by upstream dialect; better JSON, row locks, partial indexes | MySQL 8 (also supported) |
| ADR-03 | Redis for queue/cache/locks in staging/prod; `database` queue locally | JoFotara jobs need reliable retries + unique locks | — |
| ADR-04 | Outbox table for JoFotara | Never lose a legal submission; auditable | Fire-and-forget HTTP (rejected) |
| ADR-05 | Local dev against **jofotara-kit** mock server | No official public sandbox found; kit implements ISTD guide rules | Hit live API (rejected) |
| ADR-06 | PDF renderer behind an interface: start with **dompdf + Arabic fonts**, fall back to **mPDF** (strong native Arabic shaping) if golden tests fail | Upstream uses dompdf; mPDF is pure PHP (no Chrome on server) | Browsershot/Chrome (heavier ops) |
| ADR-07 | **Instance-per-tenant** (one container + one DB per shop) for R3 | Zero changes to upstream; strong isolation; simple backups | Multi-tenant package (stancl/tenancy) → revisit at ~50 tenants |
| ADR-08 | WhatsApp via Meta **Cloud API** behind a `MessagingChannel` interface | Official, template-based; swappable for a BSP | Unofficial WhatsApp web libs (rejected: ban risk) |
| ADR-09 | Payment gateway behind `PaymentGateway` interface | Stripe doesn't onboard Jordanian businesses; choose local provider later | — |
| ADR-10 | Owner app = new Filament panel + PWA, not a native app (yet) | Same stack, one codebase; upstream NativePHP can wrap it later | Flutter app (rejected for now) |
| ADR-11 | Upstream POS is moving to "fully Vue" — our POS UI work targets the Vue till | Avoid building on Livewire pages that will be deleted | — |

### 6.4 PDF/Arabic test gate
Golden tests render 5 invoices (short, long, multi-tax, credit note, mixed AR/EN names) → compare text extraction + pixel diff. If dompdf fails shaping or bidi, switch `DocumentRenderer` binding to mPDF.

## 7. Upstream patches (planned)

| # | File | Change | Upstream PR? |
|---|---|---|---|
| P1 | new migration in `plugins/webkul/support` | `currencies.rounding` precision → `decimal(12,6)`; re-apply seed rounding for all 10 currencies with 3+ decimals (JOD, KWD, BHD, OMR, TND, IQD, LYD, CLF, UYI, UYW) | Yes — real bug |
| P2 | `resources/css/app.css` | Remove RTL overrides that double-flip (`.flex-row`, margin swaps) | Yes |
| P3 | `security/src/Data/states.json` | Add Palestinian governorates | Yes |
| P4 | `AGENTS.md` | Replace with ours | No |
| P5 | POS till extension point (registry for extra components/buttons) | Only if no hook exists | Yes (discuss in upstream Discussions first) |

## 8. Environments

| Env | Where | Data | JoFotara | WhatsApp | Payments |
|---|---|---|---|---|---|
| **local** | Your Linux Mint laptop (Docker Compose) | Seed + demo perfume shop | `jofotara-kit serve` mock | Log driver / test number | none |
| **ci** | GitHub Actions | Fresh DB per run | kit validator + mock | fake | fake |
| **staging** | 1 small VPS | Copy of demo + pilot test company | mock or live with test TIN | Meta test number | sandbox |
| **production** | VPS(s), one container + DB per shop | Real | live | live | live |

Local stack (`docker-compose.eunoia.yml`): `app` (PHP 8.4 CLI with `artisan serve` for local dev; fpm + nginx for staging/prod — see `docker/production`), `queue` (`php artisan queue:work --queue=jofotara,messages,default`), `scheduler`, `postgres:16`, `redis:7`, `mailpit`, `jofotara-mock` (`node` running `npx jofotara-kit serve --port 8080`).

## 9. Quality gates (CI)

| Gate | Tool |
|---|---|
| Format | `vendor/bin/pint --test` |
| Static analysis | Larastan level 6 on `plugins/eunoia` |
| Tests | Pest (unit + feature) for `plugins/eunoia/*/tests`; upstream suites for touched plugins |
| JoFotara | Golden XML files validated by `jofotara-kit validate` |
| RTL lint | Script fails on `\b(ml|mr|pl|pr|left|right)-\d` or `text-(left|right)` in `plugins/eunoia/**/*.{blade.php,vue}` |
| i18n | Every `__('eunoia-*::…')` key exists in `ar` and `en`; no Latin letters in `ar` values except whitelisted terms |
| Visual | Playwright screenshots AR/EN at 390/1280 for key screens (owner home, POS sale, invoice) |
| Security | `composer audit`, `npm audit --omit=dev`, secret scan (gitleaks) |

## 10. Security & privacy
- Secrets (`secret_key`, WhatsApp token, gateway keys) → `encrypted` casts + `.env` only for platform secrets; never in logs (custom log processor masks `Secret-Key`, `Authorization`).
- Roles: Owner, Manager, Cashier, Accountant, Support (read-only). Cashier can't refund/discount above a limit without owner approval.
- Audit log for money-affecting changes.
- Personal data: consent records, export & delete tools; data processing agreement for each shop; check Jordan PDPL requirements before launch.
- Backups: nightly `pg_dump` per tenant → off-site object storage, 30 days; restore drill monthly.
- Plugin uninstall disabled in production (it drops tables).

## 11. Observability
- Logs: JSON to stdout → Loki/Grafana or a hosted log service.
- Metrics: queue depth per queue, JoFotara accepted/rejected/failed per hour, WhatsApp delivery rate, POS sync lag.
- Alerts: JoFotara failed > 0 for 1 h, queue depth > 100, backup missing.
- Health: `/up` (Laravel) + `php artisan eunoia:doctor` (checks keys, mock/live, queue workers, fonts, currency precision).

## 12. Hosting plan (R3 — after local is green)
1. One VPS (4 vCPU / 8 GB) runs Docker: Traefik/Caddy reverse proxy (TLS), N shop stacks (`app`+`queue`+`scheduler`) sharing one Postgres server (separate DB per shop) and Redis (separate DB index/prefix per shop).
2. Provisioning script `bin/new-tenant <slug>` → create DB, `.env`, container, run `eunoia:install --country=JO`, create owner user.
3. Domains: `<shop>.eunoia.app` (wildcard TLS).
4. Billing: subscription records in a small control-plane app (or `eunoia/saas` in a "platform" instance) with a local payment gateway; suspended tenants become read-only.
5. Move to managed Postgres + 2 app nodes when > 30 shops.
