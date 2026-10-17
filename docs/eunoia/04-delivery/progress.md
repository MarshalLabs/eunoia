# Progress log

One line per ticket: ticket · status · what works · how to test by hand · known gaps.

| Ticket | Status | What works | How to test by hand | Known gaps |
|---|---|---|---|---|
| E0-01 | Done | Fork `MarshalLabs/eunoia` cloned to `~/eunoia`; `upstream` = aureuserp; `main` created from upstream `master`, set as default branch, ruleset `protect-main` blocks force-push and deletion | `git remote -v`; GitHub → Settings → Rules | Repo is **public** — never commit `.env`/secrets. Upstream `master` branch kept on the fork, untouched |
| E0-02 | Done | Docs pack committed: `docs/eunoia`, `AGENTS.md` (replaces upstream Boost file, P4), `CLAUDE.md`, `PATCHES.md`, `.claude/agents`, `patches/`, this log | Browse `docs/eunoia/00-README.md` | Upstream Boost file kept as `docs/eunoia/agents/laravel-boost.md` (versions in it are stale, see F19) |
