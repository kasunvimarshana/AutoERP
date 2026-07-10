# Production platform health command

## Context

Platform operational health already verifies production-sensitive infrastructure readiness, including mail transport, queue configuration, and pending migrations. The check was available through the platform health service and dashboard, but there was no explicit console command or composer production gate that deploy operators could run before routing traffic.

## Change

- Added `platform:health` as a Tenant-owned console command.
- Kept the command constructor-free and injected `PlatformOperationalInfrastructureHealthService` through `handle()` so Artisan bootstrap remains safe before runtime services are resolved.
- Added `composer production:check` to run config clearing, Auth readiness, and platform operational health.
- Left `composer runtime:check` unchanged so local development startup is not blocked by production mail/queue requirements.
- Added a bootstrap contract test guarding the production check script, command registration, and constructor-free command shape.

## Verification

Run:

```bash
php artisan test
npm run typecheck -- --pretty false
npm run lint
npm run build
npm run test
composer production:check
```

`composer production:check` is intended for production-like environments with real mail and asynchronous queue configuration. Local development can continue to use `composer runtime:check`.
