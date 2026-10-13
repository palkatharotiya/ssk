# SSK Store — Workspace Rules

**Before doing any work in this codebase, you MUST activate and follow the `ssk-master-prompt` skill.**

That skill references [MASTER_PROMPT_ANTIGRAVITY.md](file:///D:/AI/SSK/Sahajanand_Sahitya_Kendra/MASTER_PROMPT_ANTIGRAVITY.md) — read it in full using `view_file` before writing or changing any code.

Key reminders:
- Never commit to `main` — use branch `audit/hardening-and-speed`.
- Phase 1 changes NO application code — only `docs/`.
- Keep plain PHP (no framework), PHP 8.0–8.3 compatible.
- Every state-changing action = POST + CSRF + auth + permission.
- Talk to the user in simple English.
- Stop and wait for approval before running migrations on non-local DB, deleting files/tables, or adding paid services.
