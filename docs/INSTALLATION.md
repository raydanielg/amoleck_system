# Installation Guide — Amoleck Physiotherapy Clinic Management System

## Requirements
- PHP 8.3+ with pdo_mysql/pdo_sqlite, mbstring, openssl, fileinfo
- Composer 2.x
- Node.js 18+ & npm
- MySQL 8+/MariaDB (production) — SQLite works for local development

## Setup
```bash
git clone <repo> && cd <repo>
composer install
npm install && npm run build
cp .env.example .env
php artisan key:generate
```

Configure `.env`:
- `DB_*` — database credentials (MySQL in production)
- `APP_NAME="Amoleck Group Company LTD"`
- `APP_ENV=production`, `APP_DEBUG=false` in production

```bash
php artisan migrate --seed
php artisan storage:link   # optional
php artisan serve          # dev only
```

## First login
- Email: `admin@amoleck.co.tz`
- Password: `Admin@123`
- You will be forced to set a new password on first login.

## Roles
- `ADMIN` — full access: settings, users, services, expense categories, audit log, all records.
- `CLINIC_STAFF` — daily operations: patients, visits, expenses, permitted reports.

## Switching to MySQL
Update `.env`:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=amoleck_clinic
DB_USERNAME=<user>
DB_PASSWORD=<pass>
```
Then run `php artisan migrate --seed`.
