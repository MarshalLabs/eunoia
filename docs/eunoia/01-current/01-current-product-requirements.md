# 01 — Current Product Requirements (AureusERP baseline)

> **What this is:** a reverse-engineered description of what AureusERP *already does*, written from reading the source code (upstream `master`, commit `44862a7`, 16 Oct 2026). It is the "before" picture. The "after" is in `02-target/04-product-requirements.md`.
>
> **Author:** Mohammad Abu-aisheh · **Date:** 17 Oct 2026 · **Status:** Baseline, read-only

---

## 1. Product summary

| Item | Value |
|---|---|
| Product | AureusERP — open-source ERP for SMEs and larger companies |
| Maintainer | Webkul (India) |
| License | MIT (free to fork, rebrand, sell, keep additions closed) |
| Stack | Laravel 13, FilamentPHP 5, Livewire 4, Tailwind 4, Vue 3 (POS till only), PHP 8.3+ |
| Database | MySQL 8 or PostgreSQL (both have a dialect class) |
| Size | 29 plugins, ~340 Eloquent models, ~240 Filament resources, ~230 test files (Pest) |
| Tenancy | One installation = one tenant. Multi-**company** inside one install, not multi-tenant SaaS. |
| Panels (apps) | `/admin` (back office), `/pos` (cashier till, PWA, offline-capable), `/` (customer portal + website) |

## 2. Who it is built for (implicit personas)

AureusERP is modelled closely on Odoo's data model (moves, journals, fiscal positions, pickings, routes). The implicit user is an **accountant or ops person in a Western SME** who:

- thinks in Odoo concepts (journal entries, reconciliation, fiscal positions)
- works on a desktop browser, in English
- sends invoices by email or PDF
- pays/gets paid by bank transfer or card

There is **no persona for a shop owner who runs everything from a phone**, which is our target.

## 3. Functional scope by module

### 3.1 Core / system plugins (always installed)
| Plugin | What it does |
|---|---|
| `support` | Companies, currencies + rates, countries/states, UoM, activity types, calendars, UTM, branding settings, RTL support, PDF helper, `money()` helper |
| `security` | Users, roles, permissions (Filament Shield), teams, settings |
| `plugin-manager` | `php artisan <plugin>:install / :uninstall`, dependency checks |
| `chatter` | Messages, followers, activities on any record (Odoo-style chatter) |
| `fields` | Custom fields on models without migrations |
| `table-views` | Saved table filters/views per user |
| `analytics` | Analytic accounts/plans |

### 3.2 Finance
| Plugin | Capabilities found in code |
|---|---|
| `accounts` | Chart of accounts, journals, journal entries (moves), taxes with repartition lines, tax groups, fiscal positions, payment terms, cash rounding, reconciliation, bank statements, sequences, incoterms. Events: `MoveCreated / MoveConfirmed / MovePaid / MoveCancelled / MoveReversed / MoveDrafted / MoveUpdated` |
| `invoices` | Customer invoices, vendor bills, credit notes, PDF preview, send by email |
| `accounting` | Reports: P&L, balance sheet, trial balance, general ledger, partner ledger, aged receivable/payable (PDF + screen) |
| `payments` | Payment methods/tokens/transactions tables — **no real payment provider integrated** |

### 3.3 Operations
| Plugin | Capabilities |
|---|---|
| `products` | Products, variants via attributes, categories, packaging, price lists + price rules, suppliers |
| `inventories` | Warehouses, locations, routes, pickings, lots/serials, scrap, replenishment |
| `sales` | Quotations, sales orders, order templates, upsell, sales teams, invoicing policy (ordered/delivered) |
| `purchases` | RFQs, purchase orders, purchase agreements, vendor pricelists |
| `manufacturing` | Bills of materials, manufacturing orders, work orders, work centers |
| `maintenance` | Equipment and maintenance requests |
| `barcode` | Barcode scanning flows |
| `point-of-sale` | Registers/sessions, opening & closing control, cash in/out, Vue till app, offline mode (IndexedDB), PWA install, receipts, refunds, bill splitting, restaurant floors/tables, kitchen printers (Epson ePOS), payment methods `cash / bank / pay_later`, terminal types `none / terminal / qr_code / iot`, global discounts, price lists, lots |

### 3.4 People
| Plugin | Capabilities |
|---|---|
| `employees` | Employees, departments, job positions, skills, work schedules |
| `time-off` | Leave types, allocations, requests, accrual plans |
| `timesheets` | Time logging on projects |
| `recruitments` | Job posts, applicants, stages |
| — | **No payroll module** |

### 3.5 Customers & content
| Plugin | Capabilities |
|---|---|
| `partners` / `contacts` | One `partners` table for customers, vendors and contacts (fields include `tax_id`, `company_registry`, `phone`, `mobile`) |
| `projects` | Projects, tasks, stages, milestones |
| `website`, `blogs` | Simple public website + blog, customer portal panel |
| `support` (helpdesk part) | Help/docs pages |

### 3.6 Platform features
- **Languages:** `en`, `ar`, `es`, `fr`, `pt_BR`. Arabic is marked RTL.
- **API:** Sanctum-protected REST under `admin/api/v1/...` for accounts, inventories, partners, products, projects, purchases, sales, POS, security, support (documented with Scribe).
- **Auth:** Login, password reset, email verification, MFA (authenticator app), role-based permissions.
- **Notifications:** Database notifications (30s polling), email (mailer default = `log`).
- **Queue:** `database` driver by default.
- **Mobile:** `nativephp/mobile` + `aureuserp/nativephp-remote` are required in composer (early mobile-app work upstream).

## 4. What it does NOT do (relevant to us)

| Missing | Why it matters for Jordan/Palestine |
|---|---|
| E-invoicing to a tax authority | Jordan requires **JoFotara** for every invoice since 1 April 2025 |
| Local chart of accounts & taxes | Seed has a generic 51-account chart and a **15 %** tax (Jordan/Palestine standard rate is **16 %**) |
| WhatsApp / SMS sending | Invoice sending methods are only `manual`, `email`, `snailmail` |
| Loyalty, coupons, gift cards | Not in POS or sales |
| Reviews / feedback / NPS | Not present |
| Local payments | No CliQ, eFAWATEERcom, PayTabs, HyperPay, PalPay, JawwalPay |
| Payroll | No payroll; nothing for Jordan Social Security |
| Amount in words (تفقيط) | Not on invoices, though `ar-php` (which can do it) is already a dependency |
| SaaS billing & tenancy | No subscriptions, no tenant isolation |
| Mobile-first owner experience | Desktop-first Filament back office |

## 5. Non-functional baseline

| Area | Current state |
|---|---|
| Performance | Fine for one company; `set_time_limit(300)` in admin panel provider hints at heavy pages |
| Offline | POS till works offline; back office does not |
| Accessibility | Filament defaults; no audit |
| i18n quality | Arabic keys exist for **100 %** of 14,552 English strings (69 left identical to English). Language is MSA, sometimes literal (e.g. "شريك" for partner, "حركة" for move) |
| Testing | Pest feature tests per plugin + Playwright; CI workflows: pest, playwright, translations check, docker publish |
| Security | Shield RBAC, MFA, CSRF, encrypted cookies. Upstream security contact: support@webkul.com |

## 6. Key takeaways

1. The **engine is strong**: real double-entry accounting, inventory, POS with offline mode. We should not rebuild any of it.
2. The **gaps are exactly the local + CX layer**: tax authority, local taxes/currencies, messaging, loyalty, payments, Arabic UX. That is the product we build.
3. The **POS already exists** (correction to my first message where I said it was missing — the README does not list it, but the code has a full POS plugin).
