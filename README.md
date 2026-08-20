# Phase 2 Delivery Tracker

A lightweight project-management tool for Phase 2 of the repairs housing
system delivery (ROCC build, replacing Servitor). Gives the team an at-a-
glance weekly view: overall status, current focus, progress, tasks, risks
& issues, decisions required, supplier activity, milestones, a 60–90 day
lookahead, and a browsable weekly archive.

PHP + PDO/MySQL, no framework — same stack and conventions as the sibling
`sor-system` app.

## Auth: shared with sor-system

This app has **no account store of its own**. It authenticates against the
`users` table that `sor-system` owns, in the same MySQL database — so
whoever can already log into sor-system can log in here with the same
username and password. Create/activate accounts in sor-system; this app
only reads `users`.

Authorization here is separate from sor-system's `app_role` (which governs
SOR rates permissions, not this app). Set `PM_ADMIN_USERNAMES` in `.env` to
a comma-separated list of sor-system usernames who should get admin
(create/edit) rights in this tool. Everyone else with a valid shared login
is view-only — matching "sole admin/developer, wider team as view-only."

## Local setup (MAMP)

```bash
cp .env.example .env
```

Point `DB_*` at the **same database** sor-system uses locally (defaults
assume MAMP on port 8889, `DB_NAME=sor_management`). Set
`PM_ADMIN_USERNAMES` to your own sor-system username.

Run the schema (adds `pm_*` tables only — `users`/`login_attempts` already
exist there from sor-system's own `sql/schema.sql`):

```
sql/schema.sql   → run in phpMyAdmin against the shared database
```

Then either point MAMP's document root here, or:

```bash
php -S localhost:8080
```

## Structure

- `includes/` — config, db, auth (reads sor-system's `users` table),
  permissions (`PM_ADMIN_USERNAMES` gate), shared functions, page layout.
- `tasks/`, `risks/`, `decisions/`, `supplier/`, `milestones/` — one CRUD
  module each (`index.php` list, `create.php`, `edit.php`), admin-only
  writes, viewer-only reads.
- `updates/` — the weekly archive: `create.php` publishes a snapshot
  (status, focus, progress, achievements, key decisions, risks raised,
  lessons learned, 60–90 day lookahead), `index.php` lists past updates,
  `view.php` shows one. The dashboard always shows the most recent
  snapshot — "weekly" is a habit, not something enforced by the schema, so
  the cadence can change without a code change.
- `index.php` — the dashboard.

## Production deployment

Same pattern as sor-system: push to GitHub, deploy via Hostinger Git
deployment or manual upload, create `.env` on the server pointing at the
**same production database** sor-system uses, run `sql/schema.sql` once.
