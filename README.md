# Server Manager — VPS Dashboard

A dashboard to keep track of active VPS instances: IP addresses, operating systems,
hosting providers, and deployment statuses.

## Stack

- Laravel 13 (PHP 8.3) · Blade · Tailwind CSS 4 (Vite)
- MySQL (dev) · SQLite in-memory (tests) · PHPUnit

## Prerequisites

PHP 8.3+, Composer, Node 20+, and MySQL 8 (with a server running locally or reachable
via `DB_*` in `.env`).

## Status

Implemented so far:

- Project baseline and test harness (`php artisan test` green)
- Domain core: `Server` model, `ServerStatus` enum, renewal-urgency logic, factories
- Authentication: register (first user becomes admin), login, logout, role column
- Dark/light theme shell with top navigation

## Setup

Create the database first — migrations fail with `Unknown database` otherwise:

```bash
mysql -u root -e "CREATE DATABASE server_manager;"
```

Then:

```bash
composer install
cp .env.example .env        # then set DB_* for your database
php artisan key:generate
php artisan migrate
npm install && npm run build
composer dev                # serve + queue + vite
```

One-shot alternative (install, `.env`, key, migrate, build — database must already exist):

```bash
composer setup
```

## Testing

```bash
composer test               # or: php artisan test
vendor/bin/pint             # code style
```

## Repository notes

Plans, specs, and agent scratch files live in `/docs` and `/.superpowers` and are
**gitignored on purpose** — planning docs, specs, and plans never ship in this repo.

## License

MIT — see `composer.json`.
