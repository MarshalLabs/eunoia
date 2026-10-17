# 02 — Current Design System (AureusERP baseline)

> **What this is:** how AureusERP looks and behaves today, extracted from the code. There is no written design system upstream; the "system" is FilamentPHP 5 defaults plus a few overrides.
>
> **Date:** 17 Oct 2026 · **Source:** upstream `master` `44862a7`

---

## 1. Foundation: Filament defaults

AureusERP does not have its own component library. Every back-office screen is a **Filament resource** (list table → form → infolist), so the design language is Filament's:

| Layer | What is used |
|---|---|
| Components | Filament tables, forms, infolists, actions, modals, widgets, notifications |
| Icons | Heroicons (`heroicon-m-*`, `heroicon-o-*`) |
| CSS | Tailwind 4 (`@import "tailwindcss"` in `resources/css/app.css`) |
| Layout | `topNavigation()` on all panels (no sidebar), `maxContentWidth(Full)` |
| Theming | `ApplyBrandSettings` middleware reads `BrandSettings` and generates an 11-shade OKLCH palette from one seed colour |
| Dark mode | Admin + POS: on. Customer portal: **off** (`darkMode(false)`) |

## 2. Tokens

### 2.1 Colour
| Token | Default | Source |
|---|---|---|
| `primary` | Filament `Color::Blue` | Panel providers (admin, pos, customer) |
| Brand override | `primary_color`, `gray_color`, `danger_color`, `info_color`, `success_color`, `warning_color` | `Webkul\Support\Settings\BrandSettings` (editable in Settings) |
| Shade generation | Lightness/chroma tables for shades 50–950, anchor shade 600 | `ApplyBrandSettings::LIGHTNESS / CHROMA` |
| Invoice PDF accent | Hard-coded `#1a4587` | `accounts/resources/views/invoice/actions/preview/index.blade.php` |

### 2.2 Typography
| Context | Font |
|---|---|
| LTR UI | Figtree (Tailwind config) / Filament default |
| RTL UI | **Cairo** loaded from Google Fonts CDN, fallback `Noto Sans Arabic`, `Segoe UI`, Tahoma |
| PDFs | **DejaVu Sans** (dompdf default) — weak Arabic shaping, needs `ar-php` glyph processing |

No type scale is defined; Filament's scale applies.

### 2.3 Spacing, radius, elevation
Filament/Tailwind defaults. No custom tokens.

### 2.4 Branding assets
`public/images/logo.svg`, `logo-full-light.svg`, `logo-full-dark.svg`, `favicon.ico`. Language flags in `public/flags/` (Arabic uses a Saudi-style `sa` flag).

## 3. RTL implementation (how Arabic works today)

1. `config/app.php → supported_locales.ar.rtl = true`.
2. `SetLocale` middleware picks locale from `?lang=` → session → user profile → app default.
3. Filament itself renders `<html dir="rtl">` server-side for Filament pages (from its own `ar` translation `layout.direction = rtl`). On top of that, the `HasRtlSupport` trait injects:
   - a **script at body start** that sets `document.documentElement.dir/lang` again with JavaScript (redundant on Filament pages),
   - a **style block** (53 lines) with `[dir="rtl"] .fi-*` overrides and the Cairo font link.
4. `resources/css/app.css` has **71 `[dir="rtl"]` rules** (305 lines) that manually flip flex rows, margins, paddings.
5. PDFs: `prepare_rtl_html()` reverses `<td>` order in table rows by DOM manipulation (rows with `colspan`/`rowspan` are skipped).
6. Money in RTL: `money()` forces locale `en` → Latin digits and English currency formatting even in Arabic.

### Problems found in this approach
| # | Problem | Effect |
|---|---|---|
| R1 | Two mechanisms set `dir` (Filament server-side + a JS script). Non-Filament views (PDF, mail, custom Blade) depend on each template remembering `$isRtl` | Inconsistent direction outside Filament; redundant code to maintain |
| R2 | `[dir="rtl"] .flex-row { flex-direction: row-reverse }` (and similar) | Browsers **already** reverse flex rows under `dir="rtl"`; any element using Tailwind's `flex-row` is double-flipped back to LTR order |
| R3 | Physical classes (`ml-*`, `mr-*`, `pl-*`, `left-*` …) used ~47 times in plugin views vs ~40 logical (`ms-*`, `me-*`, `start-*`) | Every physical class needs a manual override |
| R4 | Cairo from Google CDN | Breaks in offline POS/PWA; privacy/latency |
| R5 | Table cell reversal hack in PDFs | Totals rows (with `colspan`) are not reversed → misaligned Arabic invoices |
| R6 | Saudi flag for Arabic | Wrong signal for Jordanian/Palestinian users; a language should not be a flag |

### Problems seen on a real local install (Arabic, 17 Oct 2026)
Screenshots: `assets/current/*.png` (desktop 1440 px and mobile 390 px).

| # | What we saw | Where |
|---|---|---|
| U1 | English plural "s" glued to Arabic labels: **"فاتورةs"**, "لا يوجد فاتورةs" | Invoice list title and empty state (Filament pluralises the model label with English rules) |
| U2 | Untranslated cluster/breadcrumb labels: "Customers", "Products" | Breadcrumbs and sub-navigation |
| U3 | Seed data in English: journal "Customer Invoices", company "My Company" | Invoice form, everywhere seed data shows |
| U4 | Two top-nav items both called "الإعدادات" (Settings and Configuration) | Invoices and Inventory top bars |
| U5 | Grammar: "لا يوجد المنتجات" (should be "لا توجد منتجات") | Empty states |
| U6 | Date shown as "أكتوبر 17, 2026" (US order) | Date pickers |
| U7 | Broken user avatar: it is loaded from the external service `ui-avatars.com`, so it fails offline / behind restricted networks | Top bar, user menu |
| U8 | On mobile the invoice table needs horizontal scrolling; key columns (total, status) are off-screen | Every list page at 390 px |

Overall: the RTL layout itself mirrors correctly (nav, forms, breadcrumbs, tables). The problems are **wording, data and mobile density**, not the mirroring.

## 4. Interaction patterns

| Pattern | Today |
|---|---|
| Navigation | Top bar with module groups (Dashboard, Contact, Sale, Purchase, Maintenance, Manufacturing, Inventory, Invoice, Accounting, Project, Employee, Time off, Recruitment, Website, POS, Barcode, Plugin, Setting, Help) — **19 groups** in one bar |
| Records | Odoo-style: list → form with status bar + chatter on the side |
| Actions | Header action buttons (Confirm, Send, Print, Cancel …) |
| Feedback | Filament toast notifications, database notifications |
| POS | Separate full-screen Vue app (47 components): product grid, cart, numpad, payment, offline modal, status bar |
| Mobile | Responsive by Filament, but dense tables/forms are hard on a phone |

## 5. Content & voice

- English source strings are Odoo-like accounting language ("Journal Entry", "Fiscal Position", "Partner").
- Arabic is formal MSA, often a literal translation of the English term rather than what a Jordanian shop owner says (e.g. "شريك" instead of "عميل/مورد", "حركة قيد" for move).
- Tax is called generically "Tax"/"الضريبة"; Jordan's law calls it **ضريبة المبيعات العامة** (General Sales Tax), not VAT.

## 6. Summary

The current design system is **"Filament + patches."** It is clean and consistent for a desktop accountant, but:
- it has no brand of its own,
- RTL is retrofitted with overrides instead of being designed in,
- nothing is optimised for a phone-first shop owner,
- PDFs (the most customer-visible output) are the weakest part in Arabic.

The target design system (`02-target/05-design-system.md`) fixes this with logical properties, server-side `dir`, self-hosted Arabic fonts, a mobile "owner app" layer and a real token set.
