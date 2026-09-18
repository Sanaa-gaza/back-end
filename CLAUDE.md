# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project state

This is a stock Laravel 12 application scaffold (PHP 8.2+, `laravel/laravel` skeleton) with no custom application code yet — no controllers, models, or routes beyond the defaults (`app/Models/User.php`, the `/` welcome route). Frontend build tooling is Vite + Tailwind CSS v4, with no JS framework wired in (plain `resources/js/app.js`). Database is MySQL (via WAMP/phpMyAdmin, database `sanaa`, configured in `.env`) for local dev. Tests use SQLite in-memory (configured in `phpunit.xml`), independent of the local dev database.

## Commands

Install dependencies:
```
composer install
npm install
```

Run the full local dev stack (PHP server, queue listener, log tailing via Pail, and Vite dev server together):
```
composer run dev
```

Run each piece individually if needed:
```
php artisan serve
php artisan queue:listen --tries=1 --timeout=0
php artisan pail --timeout=0
npm run dev
```

Build frontend assets for production:
```
npm run build
```

Run tests (clears config cache first, then runs the full suite via PHPUnit/`artisan test`):
```
composer run test
```
Run a single test file or filter by name:
```
php artisan test tests/Feature/ExampleTest.php
php artisan test --filter=test_name
```

Format PHP code (Laravel Pint):
```
vendor/bin/pint
```

Database migrations:
```
php artisan migrate
php artisan migrate:fresh --seed
```

## Architecture notes

- Standard Laravel 12 structure: bootstrap-based app configuration lives in `bootstrap/app.php` (routing, middleware, and exception handling are registered there via `Application::configure()`, not in an `Http/Kernel.php`). Middleware and exception handlers should be added inside the `->withMiddleware()` / `->withExceptions()` closures in that file.
- Routes: `routes/web.php` (HTTP) and `routes/console.php` (Artisan commands). No `api.php`, `channels.php`, or auth scaffolding is present — add them as needed.
- Tests use PHPUnit with two suites, `tests/Unit` and `tests/Feature`, configured in `phpunit.xml`. The testing environment forces `DB_CONNECTION=sqlite` with `DB_DATABASE=:memory:`, array cache/session drivers, and sync queue — tests do not touch the local `database.sqlite` file.
- Autoloading (PSR-4, from `composer.json`): `App\` → `app/`, `Database\Factories\` → `database/factories/`, `Database\Seeders\` → `database/seeders/`, `Tests\` → `tests/`.

## Project context

Senaa — a marketplace connecting craftsmen with customers in Gaza.
Current sprint: Sprint 1 (authentication and registration).

- Backend spec: `docs/sprint-1-backend.md` and the `senaa-sprint1-backend` skill.
- UI designs: `docs/ui/1.png` … `13.png`.
- The original SRS is outdated — never follow it over the skill.
- Frontend is React.js, consuming this API.


## Working rules — mandatory

Before making ANY change, you must first present a plan and wait for my explicit approval.

The plan must state:
1. Exactly which files you will create or modify
2. What changes you will make in each file, in plain terms
3. Any command you intend to run
4. Anything you are unsure about

Then stop and wait. Do not write, edit, or run anything until I reply with approval.

Additional rules:
- Never modify a file I did not ask about, even if you notice a problem in it.
  Tell me about it instead and let me decide.
- Never delete or rename existing files without asking first.
- Never install a package or change composer.json without asking first.
- Never run migrate:fresh or any command that drops data without asking first.
- If my request is ambiguous, ask before assuming.
- If you disagree with my approach, say so before implementing it, not after.
