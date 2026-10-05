# Server Manager — VPS Fleet Dashboard

A small self-hosted dashboard for tracking a fleet of VPS instances in one place: IP
addresses, operating systems, hosting providers, deployment status, and renewal dates,
with summary stats and filters so billing trouble is spotted early. Built as a plain
server-rendered Laravel app — no SPA, no external services.

## Features

- **Authentication & roles** — register / log in / log out; the first registered user
  becomes `admin`, everyone after that is `member`. Admins can edit and delete any
  server; members only their own (`ServerPolicy`).
- **Dashboard** — six stat cards (total, active, payment required, inactive, apps
  deployed, renewals due ≤30 days) above a filterable server table.
- **Filters & sorting** — filter by status, provider, owner (mine/everyone), and free
  search (name/IP/OS); sort by name, status, apps, or renewal date. All state lives in
  shareable URL query params and works without JavaScript.
- **Server CRUD** — create, view, edit, and delete servers with validation (unique
  IPv4/IPv6, required fields, date and length rules) and inline error feedback.
- **Delete confirmation** — deleting opens a designed confirmation modal (native
  `<dialog>`) showing the server's name and IP; without JavaScript the delete control
  still works as a plain POST form.
- **Inline status toggle** — flip a server between active/inactive straight from the
  table row, no page reload logic, no JS.
- **Renewal urgency badges** — renewals ≤7 days render red, ≤30 days amber; rows with
  a payment-required status and an imminent renewal get a warning tint.
- **Dark / light theme** — header toggle defaulting to the OS preference, persisted in
  `localStorage`, driven by a `data-theme` attribute (Tailwind `dark:` variant).
- **Responsive** — top-nav layout, scrollable table, and card grids that work down to
  phone widths.

## Tech stack

- Laravel 13 · PHP 8.3 · Blade (server-rendered)
- Tailwind CSS 4 via Vite
- MySQL (development) · SQLite in-memory (tests)
- PHPUnit · Laravel Pint (code style)

## Prerequisites

PHP 8.3+, Composer, Node 20+, and MySQL 8 (running locally or reachable via `DB_*` in
`.env`).

## Setup

```bash
git clone <repo-url> server-manager && cd server-manager

mysql -u root -p -e "CREATE DATABASE server_manager;"   # create the DB first — migrations fail otherwise

composer install
cp .env.example .env        # then set DB_* for your database
php artisan key:generate
php artisan migrate
npm install && npm run build
php artisan serve           # or: composer dev (serve + queue + vite together)
```

One-shot alternative (install, `.env`, key, migrate, build — database must already
exist):

```bash
composer setup
```

## Testing

```bash
php artisan test            # 66 tests (SQLite in-memory), or: composer test
vendor/bin/pint             # code style (check: vendor/bin/pint --test)
```

## Security posture

Registration is **open**: anyone who can reach the instance can create an account, and
the first registered user becomes admin. This is intentional for v1 and suitable for
trusted / private deployments (LAN, VPN, single team). Before any public exposure,
tighten access — disable or gate registration, and put the app behind SSO or a reverse
proxy with auth.

## Architecture

- **Routes** (`routes/web.php`) — guest-only auth routes (hand-rolled, three small
  controllers), then `auth`-guarded dashboard + resource routes. The dashboard filter
  and sort state is encoded in URL query params and applied server-side.
- **Controllers** (`app/Http/Controllers`) — `ServerController` (dashboard + CRUD +
  status toggle) and small auth controllers; HTTP concerns only, validation inline.
- **Models & enums** — `Server` (casts, relationships, `renewalUrgency()` /
  `billingWarning()` helpers) and `User` (role); `ServerStatus` owns labels and badge
  classes, `RenewalUrgency` owns urgency text classes, so display rules live in one
  place. `ServerPolicy` enforces admin/member permissions.
- **Views** (`resources/views`) — one layout (`layouts/app.blade.php`) with top nav and
  theme toggle, `servers/{index,create,edit,show}`, and shared partials for the form,
  delete dialog, and friends. Styling is Tailwind 4 with a `dark:` variant bound to
  `data-theme`.

## Repository notes

Planning docs (plans, specs, agent scratch files) live in `/docs` and `/.superpowers`
and are **gitignored on purpose** — they never ship in this repository.

## License

MIT — see `composer.json`.
