# Installation & Quick Start

A concise step-by-step guide for getting `laravel-ddd-architect` running in a new or existing Laravel project.

---

## 1 — Require the package

```bash
composer require yourvendor/laravel-ddd-architect
```

Laravel auto-discovery registers `DDDArchitectServiceProvider` immediately.

---

## 2 — Publish configuration (recommended)

```bash
php artisan vendor:publish --tag=ddd-architect-config
```

This creates `config/ddd-architect.php`. Review the `structure` key and choose a preset:

| Preset | Best for |
|---|---|
| `ddd-layered` (default) | Most applications — shared Infrastructure + Presentation |
| `ddd-modular` | Self-contained contexts (similar to nWidart, no module system) |
| `ddd-hexagonal` | Ports & Adapters / framework-agnostic cores |
| `custom` | Full control — define every directory yourself |

You can also set the preset via an environment variable:

```bash
DDD_STRUCTURE=ddd-modular
```

---

## 3 — Scaffold your first bounded context

```bash
php artisan ddd:make-context Order
```

Output:
```
CREATED  app/Domain/Order/Entities/Order.php
CREATED  app/Domain/Order/Repositories/OrderRepositoryInterface.php
CREATED  app/Domain/Order/Events/OrderCreated.php
CREATED  app/Application/Order/UseCases/CreateOrderUseCase.php
CREATED  app/Application/Order/Commands/CreateOrderCommand.php
CREATED  app/Application/Order/Handlers/CreateOrderHandler.php
CREATED  app/Infrastructure/Persistence/Eloquent/OrderModel.php
CREATED  app/Infrastructure/Persistence/Repositories/EloquentOrderRepository.php
CREATED  app/Presentation/Http/Controllers/Api/OrderController.php
...
✓ Bounded context [Order] scaffolded successfully.
```

---

## 4 — Register the context provider

**Laravel 11+ (`bootstrap/providers.php`)**:
```php
return [
    App\Infrastructure\Providers\OrderInfrastructureServiceProvider::class,
];
```

**Laravel 10 (`config/app.php`)**:
```php
'providers' => [
    App\Infrastructure\Providers\OrderInfrastructureServiceProvider::class,
],
```

> **Tip**: Set `'auto_discover' => true` in `config/ddd-architect.php` and the package
> registers providers automatically — no manual registration needed.

---

## 5 — Run the migration

```bash
php artisan migrate
```

---

## 6 — Build your domain

Fill in the generated stubs. Every file includes `TODO` comments telling you exactly what to implement.

```bash
# Add more artefacts as you need them
php artisan ddd:make-value-object   Order OrderStatus
php artisan ddd:make-aggregate      Order OrderAggregate
php artisan ddd:make-domain-event   Order OrderWasPlaced
php artisan ddd:make-command-handler Order CancelOrder
php artisan ddd:make-query-handler  Order GetOrderById
php artisan ddd:make-model          Order Order          # Eloquent model + factory + seeder
php artisan ddd:make-listener       Order SendConfirmation --event="App\Domain\Order\Events\OrderWasPlaced"
php artisan ddd:make-policy         Order Order
```

---

## 7 — Customise stubs (optional)

```bash
php artisan ddd:publish-stubs
```

Stubs land in `resources/stubs/ddd/`. Edit any `.stub` file and the package uses your version automatically.

---

## Common commands reference

```bash
php artisan ddd:list          # List all bounded contexts
php artisan ddd:info          # Show config + all available commands
php artisan ddd:make-context --help   # Per-command help
```

All `make:*` commands accept `--force` to overwrite existing files.
