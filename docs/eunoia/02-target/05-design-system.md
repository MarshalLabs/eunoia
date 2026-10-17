# 05 — Eunoia Design System v0.1

> Arabic-first, phone-first, built on top of Filament 5 + Tailwind 4 so we don't fight the framework.
>
> **Owner:** Mohammad Abu-aisheh (UI/UX) · **Date:** 17 Oct 2026 · **Baseline:** `01-current/02-current-design-system.md`
> **Machine-readable tokens:** `05a-design-tokens.json`

---

## 1. Principles

1. **Arabic first, not Arabic too.** Every screen is designed in RTL first, then checked in LTR. Copy is written in Jordanian business Arabic, then translated to English.
2. **Thumb first.** The owner and cashier use phones and tablets. Main actions sit in the bottom third; touch targets ≥ 44 px (POS ≥ 56 px).
3. **One number that matters.** Every owner screen leads with one big number (today's sales, cash in drawer, points balance) and then details.
4. **Legal is visible.** JoFotara status, tax and QR are always visible where they matter — never hidden in a submenu.
5. **Warm, not corporate.** It's a perfume shop, not a bank: warm neutrals, a deep "oud" primary and a gold accent, generous whitespace.
6. **Don't fight Filament.** Back-office screens use Filament components with our tokens. Custom UI only for: POS skins, owner app, receipts/invoices, customer portal.

## 2. Surfaces

| Surface | Users | Tech | Design weight |
|---|---|---|---|
| **Owner app** (`/owner`) | P1 owner | New Filament panel, mobile layout, PWA | High — custom widgets |
| **POS** (`/pos`) | P2 cashier | Upstream Vue till + our theme & perfume components | High — our skin + components |
| **Back office** (`/admin`) | P3 accountant, power users | Filament + theme | Low — theme only |
| **Documents** | Customers, ISTD | Blade → PDF (invoice A4, receipt 80 mm) | High — legal + brand |
| **Messages** | P4 customer | WhatsApp templates, SMS | Medium — copy |
| **Customer portal** (`/`) | P4 | Filament customer panel | Medium |

## 3. Tokens

### 3.1 Colour
Primary is a deep teal-green ("oud leaf"), accent is a warm gold ("amber"). Neutrals are warm stone, not cold gray. Every text colour below was checked against its background (white / `#0C0A09`) and meets WCAG AA (≥ 4.5:1): e.g. primary 5.47:1, accent 5.02:1, secondary text 4.8:1, warning 5.18:1.

| Token | Light | Dark | Use |
|---|---|---|---|
| `color.primary.600` | `#0F766E` | `#2DD4BF` | Primary buttons, links, active nav |
| `color.primary.700` | `#115E59` | `#5EEAD4` | Hover/pressed |
| `color.primary.50` | `#F0FDFA` | `#042F2E` | Selected row, soft backgrounds |
| `color.accent.600` | `#B45309` | `#FBBF24` | Loyalty points, highlights, "VIP" |
| `color.accent.50` | `#FFFBEB` | `#3B2506` | Points chip background |
| `color.neutral.0` | `#FFFFFF` | `#0C0A09` | Page background |
| `color.neutral.50` | `#FAFAF9` | `#1C1917` | Cards on gray pages |
| `color.neutral.200` | `#E7E5E4` | `#44403C` | Borders |
| `color.neutral.500` | `#78716C` | `#A8A29E` | Secondary text (≥ 4.5:1 on white) |
| `color.neutral.900` | `#1C1917` | `#FAFAF9` | Primary text |
| `color.success.600` | `#15803D` | `#4ADE80` | Paid, accepted by JoFotara |
| `color.warning.600` | `#C2410C` | `#FB923C` | Pending report, low stock (orange, so it is not confused with the gold accent) |
| `color.danger.600` | `#B91C1C` | `#F87171` | Rejected, overdue, refund |
| `color.info.600` | `#1D4ED8` | `#60A5FA` | Info banners |

Filament mapping (in our panel providers and `BrandSettings` defaults): `primary` ← `#0F766E`, `gray` ← Stone, `success/warning/danger/info` as above. Shop owners can still change `primary` in Settings (white-label), but accent and status colours are fixed so meaning stays consistent.

**Status colours are never the only signal** — always an icon + text ("مقبولة ✓", "بانتظار الإرسال ⏳", "مرفوضة ✕").

### 3.2 Typography
| Role | Arabic | Latin | Notes |
|---|---|---|---|
| UI | **IBM Plex Sans Arabic** (OFL) | **Inter** (OFL) | Self-hosted `woff2` in `plugins/eunoia/theme/resources/fonts`; no Google CDN |
| Numbers | Inter with `font-variant-numeric: tabular-nums` | same | Money columns align |
| PDF | **Noto Naskh Arabic** for body, **IBM Plex Sans Arabic** for headings (TTF registered in dompdf) | Inter | Embed subsets to keep PDF small |

Type scale (rem, base 16 px; Arabic needs ~+10 % line-height):

| Token | Size / line-height | Use |
|---|---|---|
| `font.display` | 2.25 / 2.75 | Owner home big number |
| `font.h1` | 1.75 / 2.25 | Page titles |
| `font.h2` | 1.375 / 1.875 | Section titles |
| `font.body` | 1 / 1.625 | Default |
| `font.small` | 0.875 / 1.375 | Meta, table cells on mobile |
| `font.caption` | 0.75 / 1.125 | Labels, legal small print |
| `font.pos.price` | 1.5 / 2 | POS price & total |

Weights: 400, 500, 600, 700 only.

### 3.3 Spacing, radius, elevation, motion
| Token | Value |
|---|---|
| Spacing scale | 4-based: 0, 4, 8, 12, 16, 20, 24, 32, 40, 48, 64 px |
| `radius.sm / md / lg / xl / full` | 6 / 10 / 14 / 20 / 9999 px |
| `shadow.card` | `0 1px 2px rgb(28 25 23 / .06), 0 1px 3px rgb(28 25 23 / .10)` |
| `shadow.sheet` | `0 -8px 24px rgb(28 25 23 / .12)` (bottom sheets) |
| `motion.fast / base / slow` | 120 / 200 / 320 ms, `cubic-bezier(.2,.8,.2,1)`; respect `prefers-reduced-motion` |
| Touch target | ≥ 44 px; POS keys ≥ 56 px |
| Breakpoints | 390 (phone), 768 (tablet/POS), 1280 (desktop) |

## 4. RTL rules (mandatory)

1. **Logical utilities only** in Eunoia code: `ms-* me-* ps-* pe-* start-* end-* text-start text-end border-s border-e rounded-s rounded-e`. Physical `ml- mr- pl- pr- left- right- text-left text-right` are banned (lint rule in CI, see Architecture §9).
2. **Never flip flex direction manually** for RTL. `dir="rtl"` already does it. We remove the upstream `[dir="rtl"] .flex-row { row-reverse }` style of overrides via a patch, and add visual tests.
3. Icons with direction (arrows, chevrons, "back", "send") use `rtl:-scale-x-100`. Non-directional icons (search, bell, check) never flip.
4. Numbers, phone numbers, emails, URLs, IBANs, TINs: wrap in `<bdi dir="ltr">` / `unicode-bidi: isolate` so "+962 79…" never breaks.
5. Mixed text (product names like "Oud Royal 50ml"): use `dir="auto"` on inputs.
6. Server sets `<html lang dir>` — no JS direction switching.
7. Every Eunoia screen has an **AR + EN screenshot test** (Playwright) at 390 px and 1280 px.

## 5. Numbers, money, dates

| Item | Rule | Example |
|---|---|---|
| Digits | Latin digits by default (setting: Arabic-Indic) | ١٢٣ optional |
| JOD | 3 decimals, symbol after amount in Arabic | `3.765 د.أ` / `JOD 3.765` |
| ILS | 2 decimals | `12.50 ₪` |
| Amount in words | On invoices and receipts | فقط ثلاثة دنانير وسبعمائة وخمسة وستون فلساً لا غير |
| Dates | `dd/mm/yyyy` in tables, `17 تشرين الأول 2026` in documents (Levantine month names: كانون الثاني، شباط، آذار، نيسان، أيار، حزيران، تموز، آب، أيلول، تشرين الأول، تشرين الثاني، كانون الأول) | Fixes upstream "أكتوبر 17, 2026" |
| Phone | Input accepts `079…`, stored `+96279…`, shown `079 123 4567` | |
| Negative | Minus sign + red + icon, never only parentheses | `−2.500 د.أ` |

## 6. Components

### 6.1 Shared (all surfaces)
| Component | Spec |
|---|---|
| `MoneyText` | Tabular nums, currency per §5, size variants, optional "in words" |
| `StatusBadge` | Icon + text + colour (draft, posted, paid, partial, overdue, **jofotara: pending/accepted/rejected**) |
| `PhoneInput` | `+962` prefix chip, numeric keypad on mobile, normalises to E.164, WhatsApp icon if opted in |
| `CustomerChip` | Avatar initials (generated locally — no ui-avatars.com), name, phone, points badge, tier colour |
| `EmptyState` | Illustration + one sentence + one action; grammatical Arabic ("لا توجد فواتير بعد") |
| `BottomSheet` | Mobile replacement for modals (owner app + POS on phone) |
| `ConsentToggle` | WhatsApp / SMS / marketing opt-in with timestamp text |

### 6.2 Owner app
| Component | Spec |
|---|---|
| `KpiHero` | Display number + delta vs yesterday + sparkline (7 days) |
| `KpiCard` | Title, value, delta, tap → detail |
| `AlertRow` | JoFotara rejected, low stock, cash difference — icon, title, action |
| `CustomerList` | Best / at-risk tabs, swipe to WhatsApp |
| `ApprovalCard` | Discount/refund request from cashier: approve / reject with one tap |
| Bottom nav | 4 items: الرئيسية · المبيعات · العملاء · المزيد |

### 6.3 POS (perfume)
| Component | Spec |
|---|---|
| `SizePicker` | Segmented 3/6/12/30/50/100 ml with prices, 56 px keys |
| `BlendBuilder` | Choose oils + ratio (slider or ml), live price, bottle, save to customer |
| `CustomerLookup` | Numeric phone search in the cart header; creates customer inline |
| `PointsPanel` | Balance, earn preview, "use points" toggle |
| `ReceiptPreview` | Thermal 80 mm preview with QR state |
| `JoFotaraPill` | Small status in the status bar: online/queued/failed count |

### 6.4 Documents
| Document | Layout |
|---|---|
| **A4 invoice** | Header: logo, company name AR/EN, TIN, CR no., address · Title "فاتورة ضريبية" / "فاتورة" per type · Customer block with TIN/ID · Lines (no., item, qty, unit price, tax %, total) · Totals: subtotal, General Sales Tax per rate, total, **amount in words** · JoFotara QR + UUID + status · Footer: bank/CliQ alias, return policy |
| **80 mm receipt** | Logo, shop, TIN, date/time, cashier, lines, total, paid/change, points earned/balance, QR, "Thank you" line, WhatsApp opt-in short URL |
| **Credit note** | Same as invoice + "إشعار دائن" title + original invoice number & UUID + reason |

PDF engine: keep dompdf for A4 only if Arabic shaping passes our golden tests; otherwise move documents to a headless-Chrome renderer (see Architecture §6.4). Receipts print as HTML/ESC-POS from the till, not PDF.

## 7. Content & voice

### 7.1 Voice
Short, polite, Jordanian business Arabic. Second person singular, no slang in system text, friendly in WhatsApp messages.

### 7.2 Glossary (override upstream strings)
| Concept | Upstream Arabic | Eunoia Arabic | English |
|---|---|---|---|
| Partner (customer) | شريك | **عميل** | Customer |
| Partner (vendor) | شريك | **مورد** | Supplier |
| Journal entry / move | حركة / قيد | **قيد محاسبي** | Journal entry |
| Credit note | إشعار دائن | **إشعار دائن (مرتجع)** | Credit note / return |
| Tax (Jordan) | الضريبة | **ضريبة المبيعات العامة** | General Sales Tax |
| Tax (Palestine) | الضريبة | **ضريبة القيمة المضافة** | VAT |
| Register / session | السجلات النقدية / الجلسة | **الصندوق / الوردية** | Register / shift |
| Post (invoice) | ترحيل | **اعتماد** | Confirm |
| Draft | مسودة | **مسودة** | Draft |
| Points | — | **نقاط** | Points |
| Blend | — | **تركيبة** | Blend |
| Fils | — | **فلس / فلساً** | Fils |

Rules: no English plural suffixes on Arabic labels (fix "فاتورةs" by giving every resource explicit `pluralModelLabel` translations); every empty state is a full grammatical sentence.

### 7.3 WhatsApp templates (examples, to be approved by Meta)
- **Receipt:** "أهلاً {{1}} 🌿 شكراً لزيارتك {{2}}. فاتورتك رقم {{3}} بقيمة {{4}}. نقاطك الآن: {{5}}. لعرض الفاتورة: {{6}}"
- **Review:** "كيف كانت تجربتك مع {{1}}؟ قيّمنا من 1 إلى 5 بالرد على هذه الرسالة."
- **Win-back:** "اشتقنالك يا {{1}}! تركيبتك المفضلة {{2}} بانتظارك، وخصم {{3}} لغاية {{4}}."

## 8. Accessibility checklist (per screen)
- Contrast AA, focus rings visible (2 px `primary.600`, 2 px offset)
- All icons have Arabic `aria-label`
- Forms: label above field, error text below, error summary at top
- Keyboard: full POS flow usable with keyboard + barcode scanner
- `prefers-reduced-motion` respected
- Screen reader smoke test with TalkBack (Android) in Arabic for owner app

## 9. Implementation in code
| What | Where |
|---|---|
| Tokens (CSS) | `plugins/eunoia/theme/resources/css/tokens.css` using Tailwind 4 `@theme { --color-primary-600: #0F766E; … }` |
| Filament theme | `plugins/eunoia/theme/resources/css/filament/{admin,pos,owner}/theme.css` (Filament custom theme per panel) |
| Fonts | `plugins/eunoia/theme/resources/fonts/*` + `@font-face` with `font-display: swap` |
| Blade components | `plugins/eunoia/theme/resources/views/components/*` (`<x-eunoia::money>`, `<x-eunoia::status-badge>`) |
| Vue components (POS) | `plugins/eunoia/perfumery/resources/js/components/*`, registered into the till via the till's extension point (or a small upstream patch) |
| Document templates | `plugins/eunoia/documents/resources/views/{invoice-a4,receipt-80mm,credit-note}.blade.php` |
| Figma | One file "Eunoia DS v0.1" with the same token names (Figma variables) → keep in sync |

## 10. Definition of done for any UI ticket
- [ ] Designed in RTL first; AR + EN screenshots at 390/1280 px attached to PR
- [ ] Uses tokens only (no raw hex in Eunoia code)
- [ ] Logical CSS properties only
- [ ] Arabic copy reviewed against glossary
- [ ] Dark mode checked (admin, POS)
- [ ] a11y checklist §8 passed
