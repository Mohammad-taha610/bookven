# Bookven API

Laravel 10 REST API for a sports facility booking app (soccer club theme): Sanctum token auth, branches, courts, weekly slots, bookings, payments, activity history, rate limiting, and a **super admin** Blade panel for venues and app users.

- **API base path:** `/api/v1`
- **Admin panel:** `/admin` (session auth; super admin only)

### Branch access

App users (**`user`** and **`manager`** roles) are linked to one or more **branches** via `branch_user`. They only receive branches, courts, slots, and booking flows for venues they are allowed to use. Self-registered API users start with **no** branches until a super admin assigns them in **Admin → App users** (checkboxes per branch).

**`admin`** and **`super_admin`** have unrestricted access to all branches in the API. **Managers** can create or edit **courts** only under branches they are assigned to; creating or deleting **branches** via the API is limited to **`admin`** / **`super_admin`**.

Browsing branches and courts requires a **Bearer token** (`GET /api/v1/branches` and related routes are authenticated).

## Requirements

- PHP 8.1+ with extensions: `mbstring`, `openssl`, `pdo_mysql`, `tokenizer`, `xml`, `ctype`, `json`, `zip`
- **Automated tests** (`composer test`) use SQLite in memory; enable the **`pdo_sqlite`** extension, or run tests inside the provided Docker PHP image (see below).
- Composer 2.x
- MySQL 8 (or use Docker Compose in this repo)
- Optional: Redis (for cache/session/queues in production)

## Quick setup (local)

1. Clone the repo and enter the project root (`bookven`).

2. Install PHP dependencies:

   ```bash
   composer install
   ```

3. Environment file:

   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. Configure MySQL in `.env` (`DB_*`). Create the database (e.g. `bookven`).

5. Start MySQL and Redis (optional) with Docker:

   ```bash
   docker compose up -d
   ```

   Defaults: `DB_HOST=127.0.0.1`, `DB_PORT=3306`, `DB_DATABASE=bookven`, `DB_USERNAME=root`, `DB_PASSWORD=root` if you use the bundled Compose MySQL.

6. Migrate and seed:

   ```bash
   php artisan migrate:fresh --seed
   ```

7. Run the app:

   ```bash
   php artisan serve
   ```

8. **Super admin (Blade panel):** open `http://127.0.0.1:8000/admin/login`

   - Email: `superadmin@bookven.test`  
   - Password: `SuperAdmin123!`

9. **API tokens:** register or login via `/api/v1/register` and `/api/v1/login`, then send `Authorization: Bearer {token}` on protected routes.

### Seeded demo accounts (API)

| Role   | Email                 | Password  |
|--------|----------------------|-----------|
| User   | `player@bookven.test` | `password` |
| Manager| `manager@bookven.test`| `password` |
| Admin  | `admin@bookven.test`  | `password` |

## Useful Artisan commands

| Command | Purpose |
|---------|---------|
| `php artisan migrate:fresh --seed` | Reset DB and reload seed data |
| `php artisan api:clear-cache` | Clear route, config, and app cache |
| `composer test` | Run PHPUnit (`php artisan test`) |

## Docker

- **`docker-compose.yml`** — MySQL 8 and Redis 7 on ports `3306` and `6379`. Init script creates `bookven_testing` for optional host-based PHPUnit with MySQL.
- **`Dockerfile`** — Minimal PHP 8.2 CLI with `pdo_mysql`, `pdo_sqlite`, and Composer (for CI or running tests when the host PHP has no SQLite).

Run tests in Docker (from project root, after `composer install` on the host so `vendor` exists):

```bash
docker build -t bookven-php .
docker run --rm -v "%cd%:/var/www/html" -w /var/www/html bookven-php sh -c "composer install && php artisan test"
```

(On PowerShell use `${PWD}` instead of `%cd%`.)

For production, place the app behind **nginx** + **php-fpm**, set `APP_DEBUG=false`, configure Sanctum and HTTPS, and use a queue worker for `ProcessPaymentJob` if you enable async payments.

## Postman

Import **`bookven.postman_collection.json`** (project root). Set the collection variable **`base_url`** to `http://127.0.0.1:8000` (or your host). After **Login**, paste the token into **`token`** for authenticated requests.

## API overview

- **Auth:** `POST /api/v1/register`, `login`, `logout`, `password/forgot`, `password/reset`
- **Public reads:** branches, branch courts, court detail, court slots & availability, `slots/quick`, `slots/times`
- **Authenticated:** screens (`screens/home`, `screens/booking-new`), bookings CRUD flow, payments, user history
- **Staff (`admin`, `manager`, `super_admin` via API token):** create/update/delete branches and courts

Responses use a consistent envelope: `success`, `message`, `data`, `errors`.

## License

MIT.
