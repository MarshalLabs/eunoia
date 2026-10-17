# 08 — Implementation Roadmap & Backlog

> From zero to a pilot shop. **Local first** → staging → hosting → payments.
> Assumes ~20 h/week of your time plus AI agents, 2-week sprints. Dates are targets, not promises.
>
> **Date:** 17 Oct 2026

---

## 0. Milestones

| Milestone | Sprint(s) | Target date | Exit criteria |
|---|---|---|---|
| **M0 Fork & foundation** | S1–S2 | 14 Nov 2026 | Fork with upstream remote, Docker dev stack, `eunoia/core` + `theme` + `arabic` + `localization-jo` installed; JOD 3-dp works in POS; Arabic screens free of the U1–U8 issues |
| **M1 Legal counter (MVP, local)** | S3–S6 | 9 Jan 2027 | Perfume sale in POS → Arabic receipt with amount in words → invoice posted → JoFotara (mock) accepted → QR on receipt/PDF → WhatsApp receipt (test number) |
| **M2 Come back** | S7–S9 | 20 Feb 2027 | Loyalty earn/redeem, saved blends, review request, owner app home |
| **M3 Staging + pilot prep** | S10–S11 | 20 Mar 2027 | Staging VPS, backups, monitoring, onboarding wizard; live JoFotara test with a real TIN (pilot shop) |
| **M4 Go live** | S12–S13 | 17 Apr 2027 | 1–2 pilot shops live; subscription billing via local gateway; support process |
| **M5 Palestine & growth** | after | — | `localization-ps`, payroll-jo, payment links |

## 1. Epics and tickets

Size: S ≤ ½ day · M 1–2 days · L 3–5 days. "Req" = PRD IDs.

### E0 — Fork & foundation (S1)
| ID | Ticket | Size | Req | Done when |
|---|---|---|---|---|
| E0-01 | Fork `aureuserp/aureuserp` → your GitHub; add `upstream` remote; protect `main` | S | NF-06 | `git remote -v` shows both |
| E0-02 | Copy this docs pack into repo (`docs/eunoia`, `AGENTS.md`, `CLAUDE.md`, `.claude/agents`); create `PATCHES.md`, `open-questions.md` | S | — | Merged |
| E0-03 | `docker-compose.eunoia.yml`: app, queue, scheduler, postgres16, redis, mailpit, jofotara-mock | M | — | `docker compose up` → login works |
| E0-04 | CI: pint, pest (eunoia), RTL lint script, i18n key check, gitleaks | M | NF-09 | Green on a dummy PR |
| E0-05 | `plugins/eunoia/core` skeleton + `eunoia:install --country=JO` meta command + `eunoia:doctor` | M | — | Command installs core + listed plugins |
| E0-06 | **Upstream patch P1:** currency `rounding` precision + data fix for all 3+ decimal currencies; open upstream PR | S | JO-01 | DB shows JOD rounding 0.001; POS math works in JOD (see findings F1) |

### E1 — Theme, Arabic, Jordan data (S2)
| ID | Ticket | Size | Req |
|---|---|---|---|
| E1-01 | `eunoia/theme`: tokens.css, self-hosted fonts, Filament themes for admin/pos, colour defaults in BrandSettings | M | AR-06 |
| E1-02 | Local avatar generator (replace ui-avatars.com) | S | U7 |
| E1-03 | `eunoia/arabic`: glossary overrides for top 300 visible strings; fix plural labels ("فاتورةs"); grammatical empty states | L | AR-04, U1, U5 |
| E1-04 | Levantine month names + `dd/mm/yyyy` date format in Arabic; number/digit setting | M | AR-07, U6 |
| E1-05 | Amount in words service (`ar-php`) for JOD/ILS/USD + tests | M | AR-05 |
| E1-06 | RTL clean-up patch P2 (remove double-flip CSS) + Playwright AR/EN snapshots of 20 screens | M | AR-03 |
| E1-07 | `localization-jo`: Jordan taxes (16 %, reduced, 0 %, exempt) with JoFotara categories; replace 15 % seed for JO companies | M | JO-02, JO-03 |
| E1-08 | `localization-jo`: SME chart of accounts (AR/EN) — **accountant review** | L | JO-04 |
| E1-09 | Governorates Arabic names; zip optional for JO/PS; phone normalisation +962 | M | JO-05, JO-08 |
| E1-10 | Partner identities (TIN, NIN, CR) + validation | M | JO-06 |
| E1-11 | Translate seed data (journals, company, payment terms) for JO installs | S | U3 |

### E2 — JoFotara (S3–S4)
| ID | Ticket | Size | Req |
|---|---|---|---|
| E2-01 | Settings page (Client-Id, Secret-Key encrypted, TIN, income source sequence, mode) | S | JF-01 |
| E2-02 | Tables: documents, attempts, counters; ICV allocator with row lock + concurrency test | M | JF-04 |
| E2-03 | Invoice type code resolver (income/general/special × cash/receivable) + tests | M | JF-03 |
| E2-04 | `UblInvoiceBuilder` for 388 + golden files validated by jofotara-kit | L | JF-02 |
| E2-05 | Credit note 381 with billing reference + reason | M | JF-02, JF-07 |
| E2-06 | `JoFotaraClient` (Http facade, timeouts, masking) + mock-server feature tests | M | JF-05, JF-09 |
| E2-07 | Listener on `MoveConfirmed` → outbox → `SubmitDocument` job (retries, backoff, unique) | M | JF-05 |
| E2-08 | Lock observer: block edits/reset-to-draft on submitted moves | S | JF-07 |
| E2-09 | Submission dashboard + bulk retry + rejected notifications | M | JF-08 |
| E2-10 | **Spike:** read ISTD guide v1.4 end to end; answer Q1/Q3; update rules | M | Q1, Q3 |

### E3 — Documents & perfume POS (S5–S6)
| ID | Ticket | Size | Req |
|---|---|---|---|
| E3-01 | `DocumentRenderer` interface; A4 invoice template (AR/EN, TIN, tax per rate, amount in words, QR); golden PDF tests; switch to mPDF if dompdf fails | L | ADR-06 |
| E3-02 | 80 mm receipt (till) with QR state + points + opt-in | M | PF-06, JF-06 |
| E3-03 | **Spike:** how to add components/buttons to the Vue till (extension point or patch P5); check upstream POS→Vue plan status | M | ADR-11 |
| E3-04 | Bottle-size variants setup + demo perfume catalogue seeder | M | PF-01 |
| E3-05 | Blend recipe model + price formula + stock consumption | L | PF-02 |
| E3-06 | Saved blends on customer + re-order in POS | M | PF-03 |
| E3-07 | Customer lookup by phone in POS cart | M | PF-04 |
| E3-08 | Offline POS sale → later JoFotara submission → WhatsApp QR follow-up (e2e test) | M | JF-10 |

### E4 — Messaging (S6)
| ID | Ticket | Size | Req |
|---|---|---|---|
| E4-01 | `MessagingChannel` interface; WhatsApp Cloud API channel; log channel for dev | M | CX-01 |
| E4-02 | Consents (per channel/purpose) + opt-in on receipt/POS | M | CX-02 |
| E4-03 | Send invoice/receipt by WhatsApp (template + PDF link); delivery webhooks; SMS fallback | M | CX-01 |

### E5 — Come back (S7–S9)
| ID | Ticket | Size | Req |
|---|---|---|---|
| E5-01 | Loyalty program/rules/ledger + earn on `OrderPaid`, reverse on `OrderRefunded` | L | CX-03 |
| E5-02 | Redeem points in POS (discount line, same transaction) | M | CX-03 |
| E5-03 | Feedback requests after X hours + response capture | M | CX-04 |
| E5-04 | `/owner` panel: home KPIs, alerts, best/at-risk customers, PWA manifest | L | OW-01..04 |
| E5-05 | Discount/refund approval flow cashier → owner | M | OW-02 |
| E5-06 | Win-back segment + campaign send | M | CX-05 |

### E6 — Staging, hosting, go-live (S10–S13)
| ID | Ticket | Size | Req |
|---|---|---|---|
| E6-01 | Staging VPS, Caddy/Traefik TLS, deploy script, `.env` management | M | — |
| E6-02 | Backups (nightly per tenant, off-site) + restore drill | M | SA-04 |
| E6-03 | Monitoring/alerts (queues, JoFotara failures, uptime) | M | NF-05 |
| E6-04 | `bin/new-tenant` provisioning (DB, container, install, owner user) | M | SA-02 |
| E6-05 | Onboarding wizard (company, TIN, logo, printer test, JoFotara keys) | L | SA-01 |
| E6-06 | Live JoFotara test with pilot shop's real credentials (with accountant present) | M | Q1 |
| E6-07 | Payment gateway decision + subscription billing (`PaymentGateway` adapter) | L | SA-03, Q5 |
| E6-08 | Legal: terms, privacy policy, data processing agreement, PDPL check | M | NF-04 |
| E6-09 | Pilot onboarding of 1–2 perfume shops; feedback loop | — | Goals §4 |

## 2. Sprint plan

| Sprint | Dates (2026–27) | Focus | Tickets |
|---|---|---|---|
| S1 | 19 Oct – 1 Nov | Fork, dev stack, CI, core, currency fix | E0-01…06 |
| S2 | 2 – 15 Nov | Theme, Arabic, JO data | E1-01…11 |
| S3 | 16 – 29 Nov | JoFotara data + XML | E2-01…05, E2-10 |
| S4 | 30 Nov – 13 Dec | JoFotara client, jobs, lock, dashboard | E2-06…09 |
| S5 | 14 – 27 Dec | Documents + POS spike | E3-01…03 |
| S6 | 28 Dec – 10 Jan | Perfume POS + WhatsApp | E3-04…08, E4-01…03 |
| S7–S9 | 11 Jan – 21 Feb | Loyalty, feedback, owner app | E5-01…06 |
| S10–S11 | 22 Feb – 21 Mar | Staging, backups, provisioning, wizard | E6-01…05 |
| S12–S13 | 22 Mar – 18 Apr | Live JoFotara test, billing, legal, pilot | E6-06…09 |

Buffer: university exams — plan one light sprint per semester (move S7–S9 by 2 weeks if needed).

## 3. What to do this week (concrete)
1. Fork the repo, clone it on Linux Mint, follow `09-local-setup-runbook.md` until you can log in in Arabic.
2. Commit this docs pack (E0-02).
3. Do E0-06 (currency precision) — it's small, it fixes a real bug, and an upstream PR gives you visibility with Webkul.
4. Book 1 hour with an accountant who knows JoFotara: go through Q1–Q3 and the chart of accounts.
5. Visit 3 perfume shops: watch a sale, photograph a receipt, ask how they handle JoFotara today.

## 4. Risks
| Risk | Impact | Mitigation |
|---|---|---|
| JoFotara rules misunderstood | Legal | Accountant + ISTD guide + live test before pilot; never auto-send live from dev |
| Upstream breaking changes (POS → Vue) | Rework | Monthly sync branch, plugins only, spikes before building on POS UI |
| Scope creep (ERP is huge) | Never launch | MVP = legal counter for perfume shops only |
| Solo developer + studies | Delays | Agents for building, you for decisions; light sprints in exam weeks |
| WhatsApp template approval delays | CX features slip | Submit templates early (S5); SMS fallback |
| Payment gateway onboarding takes weeks | Billing late | Start application in S8; manual invoicing for first pilots |
