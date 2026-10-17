# Eunoia — documentation pack

**Eunoia (يونويا)** is a CX-first ERP for small shops in Jordan and Palestine, built as plugins on top of [AureusERP](https://github.com/aureuserp/aureuserp) (MIT). First audience: perfume-blending shops in Amman.

Prepared 17 Oct 2026 by Mohammad Abu-aisheh from a source-code audit of AureusERP `master` (`44862a7`) and a real local install (PostgreSQL 16, `--country=JO`, Arabic).

## How to use this pack
1. Unzip at the root of your fork. You get `docs/eunoia/**`, `AGENTS.md`, `CLAUDE.md`, `PATCHES.md`, `.claude/agents/*`, `patches/P1-currency-rounding/*`.
2. Read in this order: **10 (findings)** → **04 (PRD)** → **06 (architecture)** → **08 (roadmap)** → **09 (setup)**.
3. Give AI agents `AGENTS.md`; they will find the rest from there.

## Contents

| # | Document | What's in it |
|---|---|---|
| **Current state (before)** |||
| 01 | [Current product requirements](01-current/01-current-product-requirements.md) | What AureusERP does today, module by module, and what it doesn't |
| 02 | [Current design system](01-current/02-current-design-system.md) | Filament defaults, RTL implementation, problems seen in Arabic screenshots |
| 03 | [Current architecture](01-current/03-current-architecture.md) | Plugin system, dependencies, domain model, extension points, risks |
| **Target (after)** |||
| 04 | [Product requirements (PRD)](02-target/04-product-requirements.md) | Personas, goals, releases R0–R4, requirement IDs (JO-, JF-, AR-, PF-, CX-, OW-, SA-, PS-, PY-), NFRs, open questions |
| 05 | [Design system](02-target/05-design-system.md) + [tokens JSON](02-target/05a-design-tokens.json) | Principles, tokens (contrast-checked), RTL rules, money/date formats, components, documents, glossary, WhatsApp templates |
| 06 | [Architecture](02-target/06-architecture.md) | 16 Eunoia plugins, data model, JoFotara outbox flow, ADRs, patches, environments, CI gates, security, hosting |
| **AI agents** |||
| — | [`AGENTS.md`](../../AGENTS.md) / [`CLAUDE.md`](../../CLAUDE.md) | Rules for any coding agent (versions, where code goes, money, Arabic, RTL, secrets, JoFotara) |
| 07 | [Multi-agent playbook](03-agents/07-multi-agent-playbook.md) | When to use multiple agents, roles, worktrees, prompts, guardrails |
| — | [`.claude/agents/`](../../.claude/agents) | 6 ready subagents: planner, plugin-builder, jofotara-specialist, arabic-rtl-reviewer, qa-tester, code-reviewer |
| **Delivery** |||
| 08 | [Roadmap & backlog](04-delivery/08-implementation-roadmap.md) | Milestones M0–M5, 53 tickets, sprint plan to a pilot in April 2027, risks |
| 09 | [Local setup runbook](04-delivery/09-local-setup-runbook.md) | Exact steps that worked on Linux, problems and fixes |
| — | [Open questions](04-delivery/open-questions.md) | Q1–Q8 for ISTD, accountant, shops |
| — | [`PATCHES.md`](../../PATCHES.md) + `patches/P1-*` | Upstream changes; P1 (currency rounding) is written and tested |
| **Analysis** |||
| 10 | [Findings & gap analysis](05-analysis/10-findings-and-gap-analysis.md) | 25 findings with evidence and the ticket that fixes each; external sources |
| — | `assets/current/*.png` | 12 Arabic screenshots (desktop + mobile) of the current system |

## The five things to remember
1. **Don't hard-fork.** All code in `plugins/eunoia/*`; upstream stays mergeable.
2. **Fix the JOD bug first** (F1 / P1): currency rounding was truncated to 0 and breaks POS math.
3. **JoFotara is the core legal feature** — build it with an outbox, test against the jofotara-kit mock, confirm open rules with an accountant before going live.
4. **Arabic quality is a product feature**, not translation work: glossary, plurals, dates, amount in words, PDF fonts.
5. **MVP = the legal counter for perfume shops.** Loyalty, owner app and billing come after it works locally.
