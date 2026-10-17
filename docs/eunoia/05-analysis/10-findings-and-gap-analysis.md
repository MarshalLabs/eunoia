# 10 — Findings & Gap Analysis

> Everything found while analysing AureusERP for Jordan/Palestine, in one list, with evidence and the ticket that fixes it.
> **Method:** source reading (upstream `44862a7`) + a real local install on PostgreSQL 16 with `--country=JO` + Arabic screenshots (desktop 1440 px, mobile 390 px) + external research on JoFotara, taxes, payments.
>
> **Date:** 17 Oct 2026

---

## 1. Verdict

AureusERP is a **good engine** for Eunoia: MIT license, real double-entry accounting, inventory, a POS with offline mode, Arabic translations for 100 % of keys, Postgres support, and a plugin system that lets us add `plugins/eunoia/*` without touching core.

The work is concentrated in **five gaps**: (1) JoFotara, (2) Jordan/Palestine fiscal data, (3) Arabic quality and documents, (4) CX (WhatsApp, loyalty, feedback), (5) phone-first owner experience. Plus **one real bug that blocks JOD in the POS** (F1).

## 2. Findings

Severity: **P0** blocks MVP · **P1** needed for pilot · **P2** quality.

| ID | Sev | Finding | Evidence | Fix |
|---|---|---|---|---|
| F1 | **P0** | JOD rounding stored as `0.00` because `currencies.rounding` is `decimal(8,2)`; seed says `0.001`. The POS till's `floatRound/floatCompare` **throw** "precision_rounding must be positive" with rounding 0 → tips, discounts, balance/due calculations break for JOD — and for all 10 seeded currencies with 3+ decimals (BHD, CLF, IQD, JOD, KWD, LYD, OMR, TND, UYI, UYW all have `rounding = 0` after install) | DB after install: `JOD … decimal_places 3, rounding 0.00`; reproduced `floatRound(3.765,{precisionRounding:0})` → error in `till/tax/float.js` | E0-06 / patch P1 + upstream PR |
| F2 | P0 | No e-invoicing implementation. `InvoiceFormat` enum lists EU formats only (labels, no generator); nothing for JoFotara | `accounts/src/Enums/InvoiceFormat.php` | E2 epic |
| F3 | P0 | Default taxes are `15 %` (sale + purchase) even with `--country=JO`; Jordan's standard GST is 16 % | `select name, amount from accounts_taxes` → `15 %` | E1-07 |
| F4 | P1 | Generic 51-account chart of accounts, English names | `accounts/database/seeders/AccountSeeder.php` | E1-08 |
| F5 | P1 | Posted invoices can be reset to draft (`MoveWorkflow::resetToDraft`) — not allowed once reported to JoFotara | `accounts/src/Services/MoveWorkflow.php` | E2-08 |
| F6 | P1 | Invoice sending methods: download, email, post only — no WhatsApp/SMS | `InvoiceSendingMethod` enum | E4 |
| F7 | P1 | No loyalty, coupons, gift cards, reviews | grep in POS/sales | E5 |
| F8 | P1 | No amount in words on invoices, although `khaled.alshamaa/ar-php` is already installed | composer.json; no usage for words | E1-05 |
| F9 | P1 | PDFs use DejaVu Sans + a `prepare_rtl_html()` hack that reverses table cells but skips rows with colspan/rowspan (totals rows) | `support/src/helpers.php`, `PDFHandler.php` | E3-01 (golden tests, mPDF fallback) |
| F10 | P1 | English plural suffix on Arabic labels: "فاتورةs", "لا يوجد فاتورةs" | Screenshot `assets/current/mobile-invoices.png` | E1-03 |
| F11 | P1 | Untranslated breadcrumbs/clusters ("Customers", "Products"); seed data in English ("Customer Invoices", "My Company") | Screenshots `desktop-invoice-create.png`, `desktop-products.png` | E1-03, E1-11 |
| F12 | P2 | Two top-nav items both "الإعدادات"; grammar "لا يوجد المنتجات" | Screenshots | E1-03 |
| F13 | P2 | Dates in Arabic shown US-style "أكتوبر 17, 2026"; Jordan uses Levantine month names (تشرين الأول) | Screenshot | E1-04 |
| F14 | P2 | Avatar from `ui-avatars.com`; Cairo font from Google CDN → broken/slow offline (POS PWA) and leaks requests | Page HTML | E1-01, E1-02 |
| F15 | P2 | RTL CSS overrides double-flip flex rows (`[dir=rtl] .flex-row {row-reverse}`) and patch margins; ~47 physical classes in plugin views | `resources/css/app.css` (71 RTL rules) | E1-06 / patch P2 |
| F16 | P1 | Palestine: 0 governorates, currency ILS only; JO/PS both `zip_required=true` | DB query | E1-09, `localization-ps` |
| F17 | P2 | Arabic language flag is Saudi (`sa`) | `config/app.php` | E1-01 |
| F18 | P1 | Mobile list pages need horizontal scroll; key columns off-screen at 390 px | `mobile-*.png` | Owner app (E5-04) instead of squeezing back office |
| F19 | P1 | Upstream `AGENTS.md` lists Laravel 11 / Filament 4 / Livewire 3; real versions are 13 / 5 / 4 → AI agents write wrong code | `AGENTS.md` vs `composer.lock` | Replaced by our `AGENTS.md` |
| F20 | P2 | Payments plugin has tables only, no provider; Stripe does not onboard Jordanian businesses | `plugins/webkul/payments` | E6-07 |
| F21 | P1 | No payroll module (Jordan Social Security: employee 7.5 %, employer 14.25 %) | plugin list | R4 `payroll-jo` |
| F22 | P2 | Partner has `tax_id` and `company_registry` but no ID type (TIN vs national ID) needed for JoFotara buyer identification | partners migration | E1-10 |
| F23 | P2 | `plugin:uninstall` drops tables — dangerous in production | README / plugin manager | Disable in prod (architecture §10) |
| F24 | info | Upstream is rewriting the POS into one Vue app (plan dated 9 Oct 2026) | `docs/superpowers/plans/2026-10-09-pos-fully-vuejs.md` | Target Vue till only (ADR-11) |
| F25 | info | Composer merge plugin is disabled when composer runs as root → plugins not loaded | Install log | Runbook |

## 3. Requirements coverage (upstream vs needed)

| Capability | Upstream | Gap size |
|---|---|---|
| Double-entry accounting, journals, reports | ✅ | — |
| Inventory, variants, price lists | ✅ | — |
| POS (offline, PWA, receipts, refunds) | ✅ | Small (perfume components, JOD bug) |
| Arabic UI + RTL | ✅ partial | Medium (wording, plurals, dates, fonts) |
| Jordan taxes / CoA / data | ❌ | Medium |
| JoFotara | ❌ | **Large** |
| Arabic invoice PDF (legal) | ⚠️ weak | Medium |
| WhatsApp / SMS | ❌ | Medium |
| Loyalty / feedback / campaigns | ❌ | Medium–large |
| Owner mobile app | ❌ | Medium |
| SaaS (tenancy, billing, onboarding) | ❌ | Large |
| Palestine | ⚠️ data only | Medium |
| Payroll (JO) | ❌ | Large (R4) |

## 4. External facts used (re-check before launch)

| Fact | Source |
|---|---|
| JoFotara full enforcement from 1 April 2025; invoices outside JoFotara invalid for input deduction; fines up to JOD 500 per violation; ISTD issues user ID + secret key; QR returned and printed | [Flick — JoFotara guide](https://www.flick.network/en-jo/e-invoicing-jordan-jofotara) |
| UBL 2.1 base64 in JSON to `POST /core/invoices/` with `Client-Id`/`Secret-Key`; type families 011/021, 012/022, 013/023; 388/381; categories S/Z/O; no official test environment (per kit authors) | [jofotara-kit (MIT)](https://github.com/skelvar/jofotara-kit) |
| "Reporting, not clearance"; TIN 8–12 digits; gap-free ICV; Z = exempt, O = zero-rated (opposite of Peppol); income source sequence for multi-activity sellers | [Fatouraty — JoFotara guide](https://fatouraty.com/en/blog/jordan-jofotara-e-invoicing-guide) |
| Jordan GST 16 %, special 24 % (some telecom), reduced rates exist, zero-rated exports/Aqaba; registration thresholds JOD 30k services / 75k goods; bi-monthly returns | [Kintsugi — Jordan GST guide](https://trykintsugi.com/sales-tax-guides/middle-east/jordan) |
| Jordan SSC 2026: employer 14.25 %, employee 7.5 %, ceiling JOD 3,349/month; income tax 5–30 % | [RemotePass — Jordan taxes](https://remotepass.com/country/jordan/taxes) |
| Palestine VAT 16 %; no social security contributions | [PwC Tax Summaries — Palestinian territories](https://taxsummaries.pwc.com/palestinian-territories/corporate/other-taxes) |
| Stripe not supported for Jordanian businesses | [doola](https://www.doola.com/stripe-guide/how-to-open-a-stripe-account-in-jordan) |
| Gateways operating in Jordan: Amazon Payment Services, 2Checkout, PayTabs, Checkout.com, HyperPay, Telr | [Jordan Times](https://jordantimes.com/news/business-club/best-payment-gateways-jordan) |

**Conflicts to resolve:** Flick implies validation before sharing; Fatouraty says reporting after issue. Fatouraty mentions a sandbox; jofotara-kit says there is none. → Q1 in the PRD, ticket E2-10.
