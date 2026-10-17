# 09 — Local Setup Runbook (Linux Mint)

> Tested on 17 Oct 2026 with upstream commit `44862a7`, PHP 8.3, PostgreSQL 16, Node 22. These are the exact steps that worked, plus the problems hit on the way.

---

## Option A — native (fastest to start)

### 1. Install system packages
```bash
sudo apt update
sudo apt install -y php8.3-cli php8.3-fpm php8.3-pgsql php8.3-mysql php8.3-intl php8.3-gd \
  php8.3-zip php8.3-mbstring php8.3-xml php8.3-curl php8.3-bcmath php8.3-sqlite3 \
  postgresql-16 redis-server git unzip
# Composer: https://getcomposer.org/download/   Node 22: via nvm
```
> If `php8.3-*` packages are missing on your Mint version, add the `ppa:ondrej/php` repository first.

### 2. Database
```bash
sudo -u postgres psql -c "CREATE USER eunoia WITH PASSWORD 'eunoia' CREATEDB;"
sudo -u postgres psql -c "CREATE DATABASE eunoia OWNER eunoia;"
```

### 3. Code
```bash
git clone git@github.com:<you>/eunoia.git && cd eunoia
git remote add upstream https://github.com/aureuserp/aureuserp.git
composer install
cp .env.example .env
php artisan key:generate
```

### 4. `.env` (only the lines to change)
> In `.env.example` the DB lines are **commented out** (`# DB_HOST=...`). Uncomment them — just changing `DB_CONNECTION` is not enough (that was my first install error: "no password supplied").
```dotenv
APP_URL=http://127.0.0.1:8000
APP_LOCALE=ar
APP_TIMEZONE=Asia/Amman
APP_CURRENCY=JOD
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=eunoia
DB_USERNAME=eunoia
DB_PASSWORD=eunoia
QUEUE_CONNECTION=database
MAIL_MAILER=log
DEBUGBAR_ENABLED=false
JOFOTARA_BASE_URL=http://127.0.0.1:8080
```

### 5. Install the ERP (Jordan)
```bash
php artisan erp:install --no-interaction \
  --admin-name="Admin" --admin-email=admin@example.com --admin-password=admin123 \
  --country=JO                      # sets JOD as base currency
php artisan point-of-sale:install --no-interaction   # also installs products, accounts, invoices, inventories
```

### 6. Front-end assets (needed — without this the admin shows unstyled HTML)
```bash
npm ci
npm run build
php artisan filament:assets
```

### 7. Run
```bash
composer run dev        # server :8000 + queue + logs + vite
# second terminal:
npx jofotara-kit serve --port 8080
```
Open `http://127.0.0.1:8000/admin/login?lang=ar` → admin@example.com / admin123.

## Option B — Docker (recommended once E0-03 is done)
```bash
docker compose -f docker-compose.eunoia.yml up -d
docker compose -f docker-compose.eunoia.yml exec app php artisan eunoia:install --country=JO
```

## Verify the install (5-minute check)
| Check | Expected | What I saw on 17 Oct |
|---|---|---|
| `/admin/login?lang=ar` | `<html lang="ar" dir="rtl">` | ✅ |
| Currency JOD | `decimal_places=3`, `rounding=0.001` | ❌ `rounding=0.00` → bug F1, fix with patch P1 |
| Default taxes for JO | 16 % General Sales Tax | ❌ seeded `15 %` |
| Palestine governorates | 11+ | ❌ 0 |
| Invoice list title | "الفواتير" | ❌ "فاتورةs" |

SQL to check currency:
```bash
psql -h 127.0.0.1 -U eunoia eunoia -c "select name, decimal_places, rounding, active from currencies where name in ('JOD','ILS','USD');"
```

## Problems I hit (and fixes)
| Problem | Fix |
|---|---|
| `fe_sendauth: no password supplied` | DB lines in `.env` are commented out; uncomment them |
| Admin unstyled / raw links | Run `npm ci && npm run build && php artisan filament:assets` |
| Composer: "Plugins have been disabled automatically as you are running as root" | Don't run composer as root (the merge plugin that loads all `plugins/*/*` packages gets disabled). On a server use `COMPOSER_ALLOW_SUPERUSER=1` |
| Composer "Could not authenticate against github.com" behind a restricted network | Add a GitHub token: `composer config --global github-oauth.github.com <token>` |
| User avatar broken | It is loaded from `ui-avatars.com`; fixed in E1-02 |
| Laravel debug bar covers the page | `DEBUGBAR_ENABLED=false` |

## Reset everything
```bash
# safest: drop and recreate the database, then repeat steps 5–6
sudo -u postgres psql -c "DROP DATABASE eunoia;" -c "CREATE DATABASE eunoia OWNER eunoia;"
```
