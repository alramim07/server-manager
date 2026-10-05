# Server Manager — VPS Dashboard

A dashboard to keep track of active VPS instances: IP addresses, operating systems,
hosting providers, and deployment statuses.

## Stack

- Laravel 13 (PHP 8.3) · Blade · Tailwind CSS 4 (Vite)
- MySQL (dev) · SQLite in-memory (tests) · PHPUnit

## Status

Implemented so far:

- Project baseline and test harness (`php artisan test` green)

## Setup

```bash
composer install
cp .env.example .env        # then set DB_* for your database
php artisan key:generate
php artisan migrate
npm install && npm run build
composer dev                # serve + queue + vite
```

## Testing

```bash
php artisan test
vendor/bin/pint             # code style
```

## Repository notes

Plans, specs, and agent scratch files live in `/docs` and `/.superpowers` and are
**gitignored on purpose** — only application code ships in this repo.
