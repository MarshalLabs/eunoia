# CLAUDE.md

@AGENTS.md

Claude Code specifics:
- Use plan mode for any ticket that touches more than one plugin or adds a migration.
- For parallel work use git worktrees (one ticket per worktree) — see `docs/eunoia/03-agents/07-multi-agent-playbook.md`.
- The JoFotara agent skill can be installed with `npx skills add skelvar/jofotara-kit`; its MCP server runs with `npx jofotara-kit mcp`.
