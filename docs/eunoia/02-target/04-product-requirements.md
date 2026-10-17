# 04 — Product Requirements: Eunoia (يونويا)

> **Eunoia** = AureusERP engine + Jordan/Palestine localization + a customer-experience (CX) layer, built for small shops that run their business from a phone.
>
> **Owner:** Mohammad Abu-aisheh · **Version:** 0.1 (draft) · **Date:** 17 Oct 2026
> **Baseline:** `01-current/01-current-product-requirements.md`

---

## 1. Problem

Small shops in Jordan and Palestine (first target: **perfume-blending shops in Amman**) need to:

1. issue legal invoices (in Jordan every invoice must be reported to **JoFotara** since 1 April 2025, and non-compliant invoices can't be used for tax deductions),
2. sell at the counter fast, with real Arabic receipts,
3. keep customers coming back (loyalty, WhatsApp follow-ups, reviews),
4. understand their money and stock from a phone.

Today they use a mix of a paper notebook, a cheap POS, Excel and WhatsApp. Local ERPs (Daftra, Sahl, Qoyod …) cover invoicing and accounting but treat CX as an add-on. Global ERPs (Odoo, AureusERP) are strong but are not localized and are not built for a phone-first owner.

## 2. Vision

> "Everything a small shop needs to sell, stay legal, and keep customers — in Arabic, on your phone."

Positioning: **CX-first ERP**. Accounting and inventory are the engine (from AureusERP); what we sell is *customers who come back*.

## 3. Target users (personas)

| ID | Persona | Context | Main jobs |
|---|---|---|---|
| P1 | **Abu Khaled — shop owner** | Owns 1–3 perfume shops in Amman, 40s, phone-first, Arabic only, not an accountant | See today's sales and cash, approve discounts, see who his best customers are, send offers on WhatsApp |
| P2 | **Rana — cashier / perfume blender** | Works the counter, mixes custom blends, 20s | Ring up a sale in < 30 s, find a customer by phone number, record a blend recipe, print/send receipt |
| P3 | **Accountant (external)** | Part-time, visits monthly, uses desktop | Check JoFotara status, run GST return figures, reconcile cash and bank, close the month |
| P4 | **End customer** | Buys perfume, has WhatsApp | Get receipt on WhatsApp, earn points, re-order "my blend", leave a review |
| P5 | **Palestinian shop owner** (phase 2) | Ramallah/Nablus, prices in ILS, sometimes JOD/USD | Multi-currency sales and cash drawer, Palestinian VAT invoices |

## 4. Goals and success metrics

| Goal | Metric | Target (first 6 months after launch) |
|---|---|---|
| Fast counter sales | Median time from first item to paid | ≤ 30 s |
| Legal compliance | % of posted invoices accepted by JoFotara | ≥ 99 % (rest retried/fixed within 24 h) |
| Retention for the shop | Repeat-customer rate for shops using loyalty | +15 % vs. their baseline |
| Owner adoption | Owners who open the owner dashboard ≥ 4 days/week | ≥ 60 % |
| Pilot | Paying pilot shops | 5 perfume shops |

## 5. Scope

### 5.1 Release plan
| Release | Name | Contents | Environment |
|---|---|---|---|
| R0 | **Foundation** | Fork setup, local dev, Arabic/RTL fixes, design system, JO data (currency, taxes, chart of accounts, governorates) | Local |
| R1 | **Legal counter (MVP)** | POS for perfume shops, Arabic invoice & receipt (amount in words, QR), **JoFotara**, WhatsApp receipt, customer by phone | Local → staging |
| R2 | **Come back** | Loyalty points, "my blend" re-order, post-sale review/NPS request, WhatsApp campaigns, owner dashboard (mobile) | Staging → pilot |
| R3 | **Go live** | Hosting, backups, monitoring, subscription billing, onboarding wizard, customer self-service portal | Production |
| R4 | **Palestine + growth** | Palestinian VAT & multi-currency drawer, local payment links, Jordan payroll (Social Security), more verticals | Production |

### 5.2 In scope for MVP (R0 + R1)
Inventory, products & variants, POS, invoices/credit notes, contacts, JO taxes & JoFotara, Arabic outputs, WhatsApp receipt.

### 5.3 Out of scope (for now)
Manufacturing (except simple blend recipes), recruitment, projects, maintenance, website/blog, payroll (R4), Palestinian e-invoicing (no national system found yet — monitor).

We **do not uninstall** those upstream plugins from the codebase; we just don't install/enable them for customers.

## 6. Functional requirements

Priority: **M** = must (release blocker), **S** = should, **C** = could.

### 6.1 Localization — Jordan (`eunoia/localization-jo`)
| ID | Requirement | Pri | Acceptance criteria |
|---|---|---|---|
| JO-01 | JOD is the default currency with **3 decimals** (fils) everywhere: UI, POS, PDF, rounding | M | Item at 1.255 JOD × 3 = 3.765 shown and stored; currency `rounding` stores 0.001 (fix column precision) |
| JO-02 | Jordanian tax set: General Sales Tax **16 %**, reduced rates (e.g. 4 %, 1 % — confirm list with ISTD/accountant), **0 % (zero-rated)**, **exempt**, special sales tax placeholder | M | Seeder creates taxes with correct names in AR/EN and JoFotara category codes (`S` standard, `O` zero-rated, `Z` exempt — note: JoFotara's meaning of Z/O differs from Peppol) |
| JO-03 | Taxes named correctly: "ضريبة المبيعات العامة" (General Sales Tax), never "VAT" on Jordanian documents | M | Grep of JO PDFs shows no "VAT"/"القيمة المضافة" |
| JO-04 | Jordanian chart of accounts template (SME-friendly, Arabic + English names) | M | Installable template; accountant review sign-off |
| JO-05 | Governorates (12) with Arabic names; postal code optional | M | Address form doesn't require zip for JO |
| JO-06 | Partner identity fields: TIN (الرقم الضريبي), National ID (الرقم الوطني), commercial registration, ID type for JoFotara buyer | M | Validation on TIN length/format; buyer ID optional below the JoFotara threshold (confirm current threshold) |
| JO-07 | Fiscal year, date format `dd/mm/yyyy`, week starts Saturday or Sunday (configurable), timezone Asia/Amman | S | — |
| JO-08 | Phone numbers in E.164 (+962 7X XXX XXXX) with local input "07…" accepted | M | "0791234567" saved as "+962791234567" |

### 6.2 JoFotara e-invoicing (`eunoia/jofotara`)
| ID | Requirement | Pri | Acceptance criteria |
|---|---|---|---|
| JF-01 | Company settings: Client-Id, Secret-Key (encrypted), TIN, income source sequence (تسلسل مصدر الدخل), environment (mock/live) | M | Secrets never shown again after save, never logged |
| JF-02 | Build **UBL 2.1 XML** for invoices (388) and credit notes/returns (381) per ISTD technical guide | M | Output passes `jofotara-kit validate` with zero errors |
| JF-03 | Compute the 3-digit invoice type code per transaction (income/general/special sales × cash/receivable) — never hard-coded | M | Unit tests for every combination |
| JF-04 | Gap-free invoice counter (ICV) and UUID per document | M | Concurrency test: 50 parallel posts → no gaps/duplicates |
| JF-05 | Submit on `MoveConfirmed` via queued job with retries + backoff; store request/response, status, QR | M | Network down → status `pending`, retried; "HTTP 200 but rejected" → status `rejected` with reason |
| JF-06 | QR code from JoFotara printed on invoice PDF and POS receipt; **never generate our own fake QR** | M | Receipt shows QR only when status is accepted |
| JF-07 | Lock submitted invoices: no edit, no reset-to-draft; corrections only via credit note referencing original number + UUID + reason | M | `resetToDraft()` on a submitted move throws |
| JF-08 | Submission dashboard: pending / accepted / rejected, bulk retry, export | M | Accountant can find all rejected invoices in ≤ 2 clicks |
| JF-09 | Local **mock mode** using `jofotara-kit serve` for dev/test | M | `.env JOFOTARA_BASE_URL=http://127.0.0.1:8080` works end to end |
| JF-10 | POS orders: invoice per sale (or per ISTD rule for retail), offline sales queued and submitted when back online | M | Offline sale → shows "will be reported" on receipt → submitted after reconnect |

> **Open question (must confirm with ISTD guide v1.4 / a real account):** sources disagree on whether JoFotara is "report after issue" or "validate before sharing" and on whether an official sandbox exists. Design for both: issue locally, submit immediately, print QR when it arrives; if not yet accepted, receipt says "pending report" and customer gets the QR version on WhatsApp later.

### 6.3 Arabic experience (`eunoia/arabic`)
| ID | Requirement | Pri | Acceptance criteria |
|---|---|---|---|
| AR-01 | Arabic is the default language; English switchable | M | New tenant opens in Arabic |
| AR-02 | Server-rendered `dir="rtl"` and `lang="ar"`, no JS flash | M | View-source shows `dir="rtl"` |
| AR-03 | Fix RTL bugs: double-flipped flex rows, physical margins, PDF table reversal | M | Visual regression snapshots (AR vs EN) on 20 key screens |
| AR-04 | Glossary-based Arabic wording for shop owners (عميل/مورد not شريك, فاتورة, مرتجع, قيد) — override upstream strings | M | Glossary file + review by 2 native speakers |
| AR-05 | Amount in words (تفقيط) in Arabic for JOD/ILS/USD on invoices ("فقط ثلاثة دنانير وسبعمائة وخمسة وستون فلساً لا غير") | M | Unit tests incl. 1, 2, 11, 100, 1000, fils |
| AR-06 | Self-hosted Arabic fonts (UI + PDF), works offline | M | No requests to fonts.googleapis.com |
| AR-07 | Digits: Latin digits by default (common in Jordan business), optional Arabic-Indic | S | Setting toggles numerals on PDFs |
| AR-08 | Hijri date shown on invoice (optional) | C | — |
| AR-09 | Language switcher shows "العربية / English" — no country flags | S | — |

### 6.4 Point of sale for perfume shops (`eunoia/perfumery` on top of upstream POS)
| ID | Requirement | Pri | Acceptance criteria |
|---|---|---|---|
| PF-01 | Sell by **bottle size** (ml) as variants: 3, 6, 12, 30, 50, 100 ml | M | Product "Oud X" with size variants and prices |
| PF-02 | **Blend recipe**: a custom perfume = oils (ml/g) + alcohol + bottle, price by formula; stock consumed per component | M | Selling a 50 ml blend reduces oil A by 10 ml, oil B by 5 ml, alcohol 35 ml, bottle 1 |
| PF-03 | Save blend to customer ("my blend"), re-order in one tap | M | Customer profile lists saved blends |
| PF-04 | Find/create customer by phone in POS | M | Typing 9 digits finds the customer |
| PF-05 | Testers / samples tracking (stock out without sale) | S | — |
| PF-06 | Receipt: Arabic, thermal 80 mm, logo, QR, points earned, WhatsApp opt-in | M | Prints on Epson-compatible printer |

### 6.5 Customer experience (`eunoia/messaging`, `eunoia/loyalty`, `eunoia/feedback`)
| ID | Requirement | Pri | Acceptance criteria |
|---|---|---|---|
| CX-01 | Send invoice/receipt by **WhatsApp** (WhatsApp Business Cloud API, approved template) and SMS fallback | M | New `InvoiceSendingMethod` values `whatsapp`, `sms`; delivery status stored |
| CX-02 | Customer consent (opt-in/out) per channel, stored with timestamp | M | No marketing message to opted-out numbers (test) |
| CX-03 | Loyalty: points per JOD spent, redeem as discount in POS, tiers (optional) | M (R2) | Earn and redeem in same POS flow, ledger per customer |
| CX-04 | Post-purchase review/NPS request on WhatsApp after X hours; results on dashboard | S (R2) | Response rate shown per shop |
| CX-05 | Win-back campaigns: "customers who didn't buy in 60 days" → WhatsApp template | S (R2) | Segment preview before sending |
| CX-06 | Birthday / occasion offers (Ramadan, Eid, Mother's Day) | C | — |
| CX-07 | Customer portal: invoices, points, saved blends | S (R3) | Login by phone OTP |

### 6.6 Owner app (mobile-first)
| ID | Requirement | Pri | Acceptance criteria |
|---|---|---|---|
| OW-01 | Mobile home: today's sales, cash in drawer, top products, low stock, JoFotara alerts | M (R2) | Loads < 2 s on 4G; usable one-handed at 375 px |
| OW-02 | Approve discount / refund requests from cashier | S | Push or WhatsApp notification |
| OW-03 | Best customers & at-risk customers lists | S | — |
| OW-04 | Installable PWA | M | Add to home screen on Android/iOS |

### 6.7 Platform & SaaS (`eunoia/saas`, R3)
| ID | Requirement | Pri |
|---|---|---|
| SA-01 | Onboarding wizard: company, TIN, logo, branch, first products, printer test, JoFotara keys | M |
| SA-02 | One isolated tenant per shop (instance or database) | M |
| SA-03 | Subscription plans and billing through a payment provider available in Jordan (Stripe does not support Jordanian businesses directly) | M |
| SA-04 | Daily backups, restore tested monthly | M |
| SA-05 | Support: WhatsApp support button, in-app help in Arabic | S |

### 6.8 Palestine (`eunoia/localization-ps`, R4)
| ID | Requirement | Pri |
|---|---|---|
| PS-01 | VAT 16 %, Palestinian invoice wording, ILS default with JOD and USD active | M |
| PS-02 | Governorates of the West Bank and Gaza with Arabic names | M |
| PS-03 | Multi-currency cash drawer: accept ILS/JOD/USD in one sale, change in ILS, closing count per currency | M |
| PS-04 | Local payment wallets (PalPay, JawwalPay) as payment methods (manual reference first, API later) | S |
| PS-05 | Monitor Palestinian e-invoicing developments | — |

### 6.9 Payments (R3/R4)
| ID | Requirement | Pri |
|---|---|---|
| PY-01 | Payment methods: cash, card terminal (manual), **CliQ** (manual reference + reconcile), bank transfer | M |
| PY-02 | Online payment links on invoices via a local gateway (PayTabs / HyperPay / similar — choose after pricing check) | S |
| PY-03 | eFAWATEERcom bill presentment | C |

### 6.10 Payroll — Jordan (R4)
Employee SSC 7.5 %, employer 14.25 % (+1 % high-risk), salary ceiling, income-tax brackets; payslips in Arabic. Figures must be re-checked every January.

## 7. Non-functional requirements
| ID | Area | Requirement |
|---|---|---|
| NF-01 | Performance | POS add-to-cart < 100 ms (client side), invoice post < 1 s excluding JoFotara |
| NF-02 | Offline | POS sells offline ≥ 8 h; sync is idempotent |
| NF-03 | Security | Secrets encrypted at rest (`encrypted` casts), 2FA for owners, audit log of price/discount/refund changes |
| NF-04 | Privacy | Comply with Jordan's Personal Data Protection Law (2023) — consent for marketing, data export/delete on request (legal review before launch) |
| NF-05 | Reliability | JoFotara submission never silently lost: outbox table + retries + alert after 3 failures |
| NF-06 | Upgradability | ≤ 10 patched upstream files; monthly upstream merge takes ≤ 1 day |
| NF-07 | Accessibility | WCAG 2.1 AA for owner app and POS (contrast, touch targets ≥ 44 px) |
| NF-08 | Localization quality | 0 untranslated strings on customer-facing screens in AR |
| NF-09 | Testing | Every Eunoia plugin ≥ 80 % line coverage on services; JoFotara XML golden-file tests |

## 8. Assumptions & open questions
| # | Topic | Status |
|---|---|---|
| Q1 | JoFotara model (report-after vs validate-before) and sandbox availability | **Open** — read ISTD technical guide v1.4, test with a real account |
| Q2 | Exact reduced-rate list for Jordan GST | Open — accountant |
| Q3 | Retail POS: one invoice per sale vs daily summary allowed? | Open — ISTD |
| Q4 | WhatsApp provider: Meta Cloud API direct vs a BSP | Open — cost check |
| Q5 | Payment gateway for subscriptions | Open — compare PayTabs, HyperPay, others; or a foreign entity |
| Q6 | Hosting location (data residency) | Open — legal check |
| Q7 | Upstream relationship: contribute Arabic fixes back? | Recommended yes |

## 9. Pricing hypothesis (to validate in pilot)
| Plan | For | Includes |
|---|---|---|
| Starter | 1 shop, 1 register | POS, invoices, JoFotara, WhatsApp receipts |
| Growth | up to 3 shops | + loyalty, reviews, campaigns, owner app |
| Pro | multi-branch | + accountant access, API, payroll (R4) |

## 10. Dependencies on upstream
- Stay on upstream `master` releases; all Eunoia code in `plugins/eunoia/*`.
- Track upstream POS → Vue rewrite; our POS extensions must target the Vue till (not the old Livewire pages).
- Any core change goes in `PATCHES.md` and, where useful, as a PR upstream.
