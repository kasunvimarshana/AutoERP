# Frontend Environment Setup

## Prerequisites

| Tool | Version |
|------|---------|
| Node.js | ≥ 20.17 LTS |
| npm | ≥ 10 |
| PHP | 8.3+ |

## Quick Start

```bash
# 1. Install PHP dependencies
composer install

# 2. Install Node dependencies (also installs Husky hooks)
npm install

# 3. Copy env file
cp .env.example .env
php artisan key:generate

# 4. Configure environment (see below)
# 5. Run migrations
php artisan migrate

# 6. Start development servers
php artisan serve &
npm run dev
```

## Environment Variables

| Variable | Default | Description |
|----------|---------|-------------|
| `VITE_APP_URL` | _(empty)_ | API base URL; leave empty in local dev (same-origin) |
| `VITE_MODULE_INVENTORY` | `true` | Enable/disable Inventory module |
| `VITE_MODULE_POS` | `true` | Enable/disable POS module |
| `VITE_MODULE_PURCHASES` | `true` | Enable/disable Purchases module |
| `VITE_MODULE_CRM` | `true` | Enable/disable CRM module |
| `VITE_MODULE_ACCOUNTING` | `true` | Enable/disable Accounting module |
| `VITE_MODULE_REPORTING` | `true` | Enable/disable Reporting module |
| `VITE_MODULE_IDENTITY` | `true` | Enable/disable Identity (Users/Roles) module |

Example `.env` additions:

```dotenv
VITE_APP_URL=
VITE_MODULE_INVENTORY=true
VITE_MODULE_POS=true
VITE_MODULE_PURCHASES=true
VITE_MODULE_CRM=true
VITE_MODULE_ACCOUNTING=true
VITE_MODULE_REPORTING=true
VITE_MODULE_IDENTITY=true
```

## Available Scripts

| Script | Description |
|--------|-------------|
| `npm run dev` | Vite dev server with HMR |
| `npm run build` | TypeScript check + Vite production build |
| `npm run type-check` | TypeScript strict type check only |
| `npm run lint` | ESLint on all `.ts` and `.vue` files |
| `npm run lint:fix` | ESLint with auto-fix |
| `npm run format` | Prettier format all frontend files |

## Pre-commit Hooks (Husky + lint-staged)

Installed automatically via `npm install`. On every commit:

1. ESLint auto-fix on staged `.ts`/`.vue` files
2. Prettier format on staged `.ts`/`.vue` files

To skip hooks in an emergency: `git commit --no-verify`

## Production Build

```bash
npm run build
# Output: public/build/ (referenced by resources/views/app.blade.php)
```
