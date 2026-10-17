# 07 — AI Agent & Multi-Agent Playbook

> How to build Eunoia with AI coding agents (Claude Code mainly) without losing control of the code.
>
> **Date:** 17 Oct 2026

---

## 1. Do we need multi-agent?

**Short answer: not at the start. Use one agent until R0 is done, then add parallel agents for independent plugins and always a separate reviewer.**

| Phase | Setup | Why |
|---|---|---|
| R0 Foundation (first 2–3 weeks) | **1 agent**, you review every PR | Patterns (plugin skeleton, tokens, glossary, test style) are being set. Parallel agents would invent 3 different patterns. |
| R1 MVP | **2–3 builder agents in parallel** (separate git worktrees) + **1 reviewer agent** | `jofotara`, `perfumery`, `messaging` barely touch each other — safe to parallelize |
| R2+ | Same + **Arabic reviewer** and **QA/e2e** agent on a schedule | More UI and copy; regression risk grows |

Multi-agent is useful for **throughput and fresh-eyes review**, not for thinking. The hard decisions (tax mapping, JoFotara rules, UX) stay with you + an accountant.

## 2. Roles

| Agent | Scope | Tools | Can write code? |
|---|---|---|---|
| **Planner / Architect** | Turns a roadmap ticket into a step plan: files, tables, tests, risks. Checks it against the PRD and architecture | Read-only (Read, Grep, Glob, WebFetch) | No |
| **Plugin Builder** (×N) | Implements one ticket inside one `plugins/eunoia/<pkg>` in its own worktree | All, inside worktree | Yes |
| **JoFotara Specialist** | Anything in `plugins/eunoia/jofotara`: UBL mapping, type codes, ICV, client, mock tests. Uses jofotara-kit MCP/skill | All + jofotara-kit MCP | Yes |
| **Arabic & RTL Reviewer** | Reviews strings against glossary, grammar, plurals, RTL classes, screenshots AR/EN | Read, Grep, Playwright screenshots | Only `resources/lang/ar` fixes |
| **QA / Test Agent** | Writes missing Pest tests, runs suites, Playwright smoke at 390/1280, reports failures | All (tests only) | Tests only |
| **Code Reviewer** | Fresh context, reviews a PR diff against `AGENTS.md` rules + DoD; never saw the implementation | Read-only + `git diff` | No |
| **Upstream Sync Agent** (monthly) | Merges `upstream/master` into `upstream-sync`, runs all tests, lists breakages in Eunoia plugins | All on sync branch | Fixes in `plugins/eunoia` only |

## 3. Workflow

```
You pick ticket E1-07 from the roadmap
  └─ Planner agent → plan.md (comment on the issue)
       └─ You approve / edit the plan           ← human gate 1
            └─ Builder agent in worktree feat/E1-07 → code + tests + screenshots → opens PR
                 ├─ QA agent runs suites, adds missing tests
                 ├─ Arabic reviewer (if UI/strings)
                 └─ Code reviewer (fresh context) → review comments
                      └─ You review + merge       ← human gate 2
```

Rules:
- **One ticket = one worktree = one branch = one PR.**
- Two builders never touch the same plugin at the same time.
- Shared files (`plugins/eunoia/core`, `theme`, translations of another plugin) → only one agent at a time; the others wait or ask.
- Reviewer agents must start with a clean context (new session / subagent), so the work isn't grading itself.

## 4. Setting it up in Claude Code

### 4.1 Worktrees
```bash
git worktree add ../eunoia-E1-07 -b feat/E1-07
cd ../eunoia-E1-07 && claude      # agent works here only
# each worktree needs its own DB to avoid migration clashes:
#   DB_DATABASE=eunoia_e1_07 in that worktree's .env
```

### 4.2 Subagent definitions
Copy `.claude/agents/*.md` from this docs pack into the repo root. Files included:
- `.claude/agents/planner.md`
- `.claude/agents/plugin-builder.md`
- `.claude/agents/jofotara-specialist.md`
- `.claude/agents/arabic-rtl-reviewer.md`
- `.claude/agents/qa-tester.md`
- `.claude/agents/code-reviewer.md`

Use them by name: *"Use the planner agent to plan E1-07"*, *"Ask the code-reviewer agent to review PR #12"*.

### 4.3 MCP servers worth adding (`.mcp.json`)
| Server | Why |
|---|---|
| Laravel Boost (`laravel/boost` is already a dev dependency; run `php artisan boost:install` and follow its MCP setup) | Version-correct Laravel/Filament docs search, DB schema, tinker |
| jofotara-kit (`npx jofotara-kit mcp`) | `validate_invoice`, `get_template`, `list_rules`, `explain_rule` |
| Playwright MCP | Screenshots AR/EN, clicking through POS |
| GitHub (`gh`) | Issues/PRs |

## 5. Prompt templates

### 5.1 Plan a ticket
```
Use the planner agent.
Ticket: E1-07 "Queue JoFotara documents on MoveConfirmed" (see docs/eunoia/04-delivery/08-implementation-roadmap.md).
Requirements: JF-04, JF-05, JF-07 in docs/eunoia/02-target/04-product-requirements.md.
Read AGENTS.md, the architecture §5.1, and upstream plugins/webkul/accounts/src/Services/MoveWorkflow.php.
Output: a numbered plan with files to create, migrations, events/listeners, tests (names), risks, and open questions. Do not write code.
```

### 5.2 Build
```
Implement the approved plan for E1-07 in this worktree only.
Follow AGENTS.md strictly (plugins/eunoia only, ar+en strings, logical CSS, no secrets, mock JoFotara).
Write Pest tests first. Run: vendor/bin/pint --dirty && vendor/bin/pest plugins/eunoia/jofotara.
Stop and ask me if you need to touch plugins/webkul or add a dependency.
Finish with: summary, files changed, test output, open questions.
```

### 5.3 Review
```
Use the code-reviewer agent on the diff of branch feat/E1-07 vs main.
Check against AGENTS.md §3 rules and §6 Definition of Done, PRD requirement IDs in the commit messages, and money/rounding correctness for JOD (3 decimals).
Report only real problems with file:line and a suggested fix. No style nitpicks Pint would fix.
```

### 5.4 Arabic review
```
Use the arabic-rtl-reviewer agent on plugins/eunoia/<pkg>.
Check every ar string against the glossary (design system §7.2), grammar, plural labels, and that no English text shows in Arabic UI.
Take Playwright screenshots of <routes> in ar and en at 390 and 1280 px and list visual RTL problems.
```

## 6. Guardrails for agents
- Agents never get real JoFotara credentials, WhatsApp tokens, or production `.env`. Local `.env` uses fakes.
- `git push --force`, branch deletion, `*:uninstall`, DB drops: not allowed without you.
- Every agent PR states which requirement IDs it covers and what it did **not** do.
- If an agent is unsure about a tax/legal rule, it writes the question into `docs/eunoia/04-delivery/open-questions.md` instead of guessing.

## 7. Cost & time sense
- One focused builder session per ticket (≈ 1–3 h of agent time) + your review (≈ 20–40 min).
- Parallel builders multiply your **review** load, not just output. Three agents is the practical max for one reviewer.
