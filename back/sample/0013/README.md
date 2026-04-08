# Laravel DDD Architect

[![CI](https://github.com/archify/laravel-ddd-architect/actions/workflows/ci.yml/badge.svg)](https://github.com/archify/laravel-ddd-architect/actions)
[![PHP](https://img.shields.io/badge/PHP-8.1%2B-blue)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-10%20%7C%2011%20%7C%2012-red)](https://laravel.com)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)

A **fully dynamic, customisable, extensible, and reusable** Laravel package that implements **Domain-Driven Design (DDD)** with **CQRS** support — with **zero third-party module library dependencies**.

Every structural aspect — layer names, directory trees, namespace roots, base paths, architecture modes, stub templates, and provider patterns — is driven entirely by a single published configuration file.

---

## Table of Contents

1. [Features](#features)
2. [Requirements](#requirements)
3. [Installation](#installation)
4. [Quick Start](#quick-start)
5. [Architecture Modes](#architecture-modes)
6. [All Artisan Commands](#all-artisan-commands)
7. [Stub Token Reference](#stub-token-reference)
8. [Customisation Guide](#customisation-guide)
9. [Auto-Discovery](#auto-discovery)
10. [Extension Patterns](#extension-patterns)
11. [Configuration Reference](#configuration-reference)
12. [Example Application](#example-application)
13. [Testing](#testing)
14. [FAQ](#faq)

---

## Features

- **Zero third-party module dependencies** — built entirely on Laravel's own filesystem, console, and container primitives
- **Three architecture modes** — `layered`, `modular`, and `flat`, all switchable via config
- **Fully dynamic** — every directory name, layer name, and namespace root is driven by the published config file
- **Fully customisable** — publish and edit any stub template; the package always prefers your custom version
- **Fully extensible** — add new generators or Artisan commands by extending two abstract classes, no core changes needed
- **Complete Artisan command suite** — 14 commands covering every DDD artefact
- **Shared Kernel scaffolding** — auto-generates production-quality `Uuid`, `Email`, `Money` value objects and base contracts on first run
- **Auto-discovery** — the ServiceProvider scans your context directories and registers each context's provider automatically
- **CQRS support** — `Command`/`Handler` and `Query`/`Handler` pairs generated together with correct cross-namespace imports
- **Every stub is self-documenting** — inline `TODO` guidance tells developers exactly what to implement
- **Full test suite** — unit tests for every generator, renderer, and resolver; feature tests for every Artisan command

---

## Requirements

| Dependency | Version |
|---|---|
| PHP | 8.1+ |
| Laravel | 10, 11, or 12 |

---

## Installation

```bash
composer require archify/laravel-ddd-architect
```

Laravel's package auto-discovery will register the service provider and `DddArchitect` facade automatically.

### Publish the configuration file

```bash
php artisan vendor:publish --tag=ddd-architect-config
```

This creates `config/ddd-architect.php`. Every structural decision the package makes is controlled from here.

### Publish stub templates (optional)

```bash
php artisan ddd:stubs:publish
```

This copies all built-in stubs to `resources/stubs/ddd/`. Edit any stub and the package will automatically use your version instead of the built-in default.

---

## Quick Start

```bash
# 1. Scaffold a new bounded context (creates all directories + ServiceProvider + Shared Kernel)
php artisan ddd:make:context Ordering

# 2. Generate a domain entity
php artisan ddd:make:entity Ordering Order

# 3. Generate a repository interface + Eloquent implementation pair
php artisan ddd:make:repository Ordering Order

# 4. Generate a CQRS command + handler
php artisan ddd:make:command Ordering CreateOrder

# 5. Generate a CQRS query + handler
php artisan ddd:make:query Ordering GetOrder

# 6. List all bounded contexts
php artisan ddd:list

# 7. Show package info and all available commands
php artisan ddd:info
```

After running these commands your `src/Ordering/` directory will contain:

```
src/Ordering/
├── Domain/
│   ├── Entities/Order.php
│   ├── Repositories/OrderRepositoryInterface.php
│   └── ...
├── Application/
│   ├── Commands/CreateOrderCommand.php
│   ├── Handlers/CreateOrderHandler.php
│   ├── Queries/GetOrderQuery.php
│   └── Handlers/GetOrderQueryHandler.php
└── Infrastructure/
    ├── Persistence/Repositories/EloquentOrderRepository.php
    └── Providers/OrderingServiceProvider.php
```

---

## Architecture Modes

Set `DDD_MODE` in your `.env` or change `mode` in `config/ddd-architect.php`.

### `layered` (default)

Classic DDD layout. Contexts live alongside each other under a single `src/` root:

```
src/
├── Shared/                          ← Shared Kernel (contracts + value objects)
├── Ordering/
│   ├── Domain/
│   ├── Application/
│   ├── Infrastructure/
│   └── Presentation/
└── Billing/
    └── ...
```

**composer.json autoload:**
```json
{
  "autoload": {
    "psr-4": {
      "App\\": "src/"
    }
  }
}
```

---

### `modular`

Each context is a self-contained module under `app/Modules/`:

```
app/Modules/
├── Ordering/
│   ├── Domain/
│   ├── Application/
│   ├── Infrastructure/
│   └── Presentation/
└── Billing/
    └── ...
```

**composer.json autoload:**
```json
{
  "autoload": {
    "psr-4": {
      "App\\Modules\\": "app/Modules/"
    }
  }
}
```

---

### `flat`

All contexts live directly under `app/`:

```
app/
├── Ordering/
│   ├── Domain/
│   ├── Application/
│   └── Infrastructure/
└── Billing/
    └── ...
```

**composer.json autoload:**
```json
{
  "autoload": {
    "psr-4": {
      "App\\": "app/"
    }
  }
}
```

---

## All Artisan Commands

All make commands accept `--force` to overwrite existing files.

---

### `ddd:make:context`

Scaffold a complete bounded context directory tree with all DDD layers.

```bash
php artisan ddd:make:context {context} [--force]
```

**Example:**
```bash
php artisan ddd:make:context Ordering
```

**Creates:**
- All layer directories (Domain, Application, Infrastructure, Presentation) with every subdirectory defined in config
- `.gitkeep` files to ensure Git tracks empty directories
- `{Context}ServiceProvider.php` in `Infrastructure/Providers/`
- Shared Kernel contracts and value objects in `src/Shared/` (once only)

---

### `ddd:make:entity`

Generate a Domain Entity class.

```bash
php artisan ddd:make:entity {context} {name} [--force]
```

**Example:**
```bash
php artisan ddd:make:entity Ordering Order
# → src/Ordering/Domain/Entities/Order.php
```

The generated class implements `EntityContract`, uses `Uuid` for identity, includes named constructors (`create`, `reconstitute`), and has a domain event queue.

---

### `ddd:make:value-object`

Generate an immutable Domain Value Object.

```bash
php artisan ddd:make:value-object {context} {name} [--force]
```

**Example:**
```bash
php artisan ddd:make:value-object Ordering OrderStatus
# → src/Ordering/Domain/ValueObjects/OrderStatus.php
```

---

### `ddd:make:aggregate`

Generate an Aggregate Root class.

```bash
php artisan ddd:make:aggregate {context} {name} [--force]
```

**Example:**
```bash
php artisan ddd:make:aggregate Ordering Order
# → src/Ordering/Domain/Aggregates/Order.php
```

The generated class implements `AggregateRootContract` and includes a `raise()` helper for domain events.

---

### `ddd:make:event`

Generate a Domain Event (name in past tense by convention).

```bash
php artisan ddd:make:event {context} {name} [--force]
```

**Example:**
```bash
php artisan ddd:make:event Ordering OrderWasPlaced
# → src/Ordering/Domain/Events/OrderWasPlaced.php
```

---

### `ddd:make:service`

Generate a Domain Service class.

```bash
php artisan ddd:make:service {context} {name} [--force]
```

**Example:**
```bash
php artisan ddd:make:service Ordering PricingService
# → src/Ordering/Domain/Services/PricingService.php
```

---

### `ddd:make:specification`

Generate a Domain Specification (business rule predicate).

```bash
php artisan ddd:make:specification {context} {name} [--force]
```

**Example:**
```bash
php artisan ddd:make:specification Ordering OrderIsEligibleForDiscount
# → src/Ordering/Domain/Specifications/OrderIsEligibleForDiscount.php
```

---

### `ddd:make:repository`

Generate a Repository Interface **and** its Eloquent implementation together.

```bash
php artisan ddd:make:repository {context} {name} [--force]
```

**Example:**
```bash
php artisan ddd:make:repository Ordering Order
# → src/Ordering/Domain/Repositories/OrderRepositoryInterface.php
# → src/Ordering/Infrastructure/Persistence/Repositories/EloquentOrderRepository.php
```

Register the binding in your `{Context}ServiceProvider::register()`:

```php
$this->app->bind(
    \App\Ordering\Domain\Repositories\OrderRepositoryInterface::class,
    \App\Ordering\Infrastructure\Persistence\Repositories\EloquentOrderRepository::class,
);
```

---

### `ddd:make:command`

Generate a CQRS **Command + Handler** pair.

```bash
php artisan ddd:make:command {context} {name} [--force]
```

**Example:**
```bash
php artisan ddd:make:command Ordering CreateOrder
# → src/Ordering/Application/Commands/CreateOrderCommand.php
# → src/Ordering/Application/Handlers/CreateOrderHandler.php
```

The handler already imports the correct command namespace.

---

### `ddd:make:query`

Generate a CQRS **Query + Handler** pair.

```bash
php artisan ddd:make:query {context} {name} [--force]
```

**Example:**
```bash
php artisan ddd:make:query Ordering GetOrder
# → src/Ordering/Application/Queries/GetOrderQuery.php
# → src/Ordering/Application/Handlers/GetOrderQueryHandler.php
```

---

### `ddd:make:dto`

Generate a Data Transfer Object.

```bash
php artisan ddd:make:dto {context} {name} [--force]
```

**Example:**
```bash
php artisan ddd:make:dto Ordering CreateOrder
# → src/Ordering/Application/DTOs/CreateOrderDto.php
```

---

### `ddd:list`

List all discovered bounded contexts.

```bash
php artisan ddd:list
```

Scans the configured base path and shows every context directory alongside its registration status and path.

---

### `ddd:stubs:publish`

Publish all built-in stub templates for customisation.

```bash
php artisan ddd:stubs:publish [--force]
```

Copies stubs to `resources/stubs/ddd/`. Once published, the package prefers your version over the built-in default.

---

### `ddd:info`

Display package configuration, stub resolution order, and all available commands.

```bash
php artisan ddd:info
```

---

## Stub Token Reference

All stub files use `{{ tokenName }}` syntax (spaces inside braces are optional).

| Token | Example value | Description |
|---|---|---|
| `{{ className }}` | `OrderItem` | PascalCase class name |
| `{{ classSnake }}` | `order_item` | snake\_case class name |
| `{{ classKebab }}` | `order-item` | kebab-case class name |
| `{{ classCamel }}` | `orderItem` | camelCase class name |
| `{{ classLower }}` | `orderitem` | lower-case class name |
| `{{ namespace }}` | `App\Ordering\Domain\Entities` | Full PHP namespace for the generated file |
| `{{ contextName }}` | `Ordering` | PascalCase context name |
| `{{ contextKebab }}` | `ordering` | kebab-case context name |
| `{{ contextSnake }}` | `ordering` | snake\_case context name |
| `{{ contextCamel }}` | `ordering` | camelCase context name |
| `{{ rootNamespace }}` | `App` | PSR-4 root namespace from config |
| `{{ date }}` | `2026-01-01` | Generation date (Y-m-d) |
| `{{ year }}` | `2026` | Current year |
| `{{ commandNamespace }}` | `App\Ordering\Application\Commands` | Used in handler stubs |
| `{{ commandName }}` | `CreateOrderCommand` | Used in handler stubs |
| `{{ queryNamespace }}` | `App\Ordering\Application\Queries` | Used in query handler stubs |
| `{{ queryName }}` | `GetOrderQuery` | Used in query handler stubs |
| `{{ interfaceNamespace }}` | `App\Ordering\Domain\Repositories` | Used in repository impl stubs |
| `{{ interfaceName }}` | `OrderRepositoryInterface` | Used in repository impl stubs |
| `{{ contextNamespace }}` | `App\Ordering` | Used in provider stubs |

---

## Customisation Guide

### Publishing stubs

```bash
php artisan ddd:stubs:publish
```

All stubs land in `resources/stubs/ddd/`:

```
resources/stubs/ddd/
├── domain/
│   ├── entity.stub
│   ├── value-object.stub
│   ├── aggregate.stub
│   ├── event.stub
│   ├── service.stub
│   ├── specification.stub
│   └── repository-interface.stub
├── application/
│   ├── command.stub
│   ├── command-handler.stub
│   ├── query.stub
│   ├── query-handler.stub
│   └── dto.stub
├── infrastructure/
│   ├── eloquent-repository.stub
│   └── provider.stub
└── shared/
    ├── aggregate-root-contract.stub
    ├── entity-contract.stub
    ├── repository-contract.stub
    ├── uuid.stub
    ├── email.stub
    └── money.stub
```

### Stub resolution order

1. Paths listed in `config('ddd-architect.stub_paths')` — checked in order
2. `{project-root}/stubs/ddd/` — project-level overrides
3. Package built-in stubs — always the fallback

The first match wins. You only need to publish the stubs you want to customise.

### Renaming layers and directories

In `config/ddd-architect.php`:

```php
'layers' => [
    'domain'         => 'Domain',       // rename to e.g. 'Core'
    'application'    => 'Application',  // rename to e.g. 'UseCases'
    'infrastructure' => 'Infrastructure',
    'presentation'   => 'Presentation',
],

'domain_directories' => [
    'entities'  => 'Entities',          // rename to e.g. 'Models'
    'events'    => 'Events',
    // ...
],
```

All generators read these config values — no code changes needed.

### Changing architecture mode

In `.env`:

```env
DDD_MODE=modular
```

Or in `config/ddd-architect.php`:

```php
'mode' => 'modular',
```

---

## Auto-Discovery

When `config('ddd-architect.auto_discover')` is `true` (the default), the `DddArchitectServiceProvider` scans the configured base path at boot time and automatically registers each context's `ServiceProvider`.

**Provider class resolution** follows the pattern set in `config('ddd-architect.provider_pattern')`:

```php
'provider_pattern' => '{namespace}\\{context}\\Infrastructure\\Providers\\{context}ServiceProvider',
```

For a context named `Ordering` in `layered` mode with namespace root `App`, this resolves to:

```
App\Ordering\Infrastructure\Providers\OrderingServiceProvider
```

If the class exists, it is registered with the Laravel container automatically. **You do not need to add anything to `config/app.php`**.

### Disabling auto-discovery

```php
// config/ddd-architect.php
'auto_discover' => false,
```

Then register context providers manually in `app/Providers/AppServiceProvider.php`:

```php
$this->app->register(\App\Ordering\Infrastructure\Providers\OrderingServiceProvider::class);
```

---

## Extension Patterns

### Adding a custom generator

1. Create a class that extends `AbstractGenerator`:

```php
<?php

namespace App\Generators;

use Archify\DddArchitect\Generators\AbstractGenerator;

final class PolicyGenerator extends AbstractGenerator
{
    public function stubKey(): string { return 'domain/policy'; }
    public function label(): string   { return 'Domain Policy'; }

    protected function targetPath(string $context, string $className): string
    {
        return $this->buildPath(
            $context,
            config('ddd-architect.layers.domain'),
            config('ddd-architect.domain_directories.policies'),
            "{$className}Policy.php"
        );
    }

    protected function namespace(string $context, string $className): string
    {
        return $this->buildNamespace(
            $context,
            config('ddd-architect.layers.domain'),
            config('ddd-architect.domain_directories.policies')
        );
    }
}
```

2. Create `resources/stubs/ddd/domain/policy.stub`.

3. Bind in your `AppServiceProvider`:

```php
$this->app->bind(\App\Generators\PolicyGenerator::class, fn ($app) =>
    new \App\Generators\PolicyGenerator(
        $app->make(\Archify\DddArchitect\Support\StubRenderer::class),
        $app->make(\Archify\DddArchitect\Support\FileGenerator::class),
        $app->make(\Archify\DddArchitect\Support\ContextResolver::class),
    )
);
```

### Adding a custom Artisan command

Extend `AbstractDddCommand`:

```php
<?php

namespace App\Console\Commands;

use App\Generators\PolicyGenerator;
use Archify\DddArchitect\Commands\AbstractDddCommand;

final class MakePolicyCommand extends AbstractDddCommand
{
    protected $signature = 'ddd:make:policy
        {context : Bounded context name}
        {name    : Policy class name}
        {--force : Overwrite existing file}';

    protected $description = 'Generate a Domain Policy class';

    protected function generators(): array
    {
        return [app(PolicyGenerator::class)];
    }
}
```

Register it in your `AppServiceProvider`:

```php
if ($this->app->runningInConsole()) {
    $this->commands([\App\Console\Commands\MakePolicyCommand::class]);
}
```

### Accessing the context registry at runtime

```php
use Archify\DddArchitect\Facades\DddArchitect;

// List all registered contexts
$contexts = DddArchitect::all();

// Check if a context exists
if (DddArchitect::has('Ordering')) {
    $meta = DddArchitect::get('Ordering');
    // $meta['path'], $meta['namespace'], $meta['provider']
}
```

---

## Configuration Reference

Full `config/ddd-architect.php` with all keys:

```php
return [
    // Architecture layout: 'layered' | 'modular' | 'flat'
    'mode' => env('DDD_MODE', 'layered'),

    // Filesystem base path per mode
    'paths' => [
        'layered'  => base_path('src'),
        'modular'  => base_path('app/Modules'),
        'flat'     => base_path('app'),
    ],

    // PSR-4 namespace root per mode (must match composer.json autoload)
    'namespaces' => [
        'layered'  => 'App',
        'modular'  => 'App\\Modules',
        'flat'     => 'App',
    ],

    // Shared Kernel location
    'shared_kernel' => [
        'path'           => base_path('src/Shared'),
        'namespace'      => 'App\\Shared',
        'auto_scaffold'  => true,       // scaffold on first context creation
    ],

    // Layer directory names (rename freely)
    'layers' => [
        'domain'         => 'Domain',
        'application'    => 'Application',
        'infrastructure' => 'Infrastructure',
        'presentation'   => 'Presentation',
    ],

    // Domain subdirectory names
    'domain_directories' => [
        'entities'       => 'Entities',
        'value_objects'  => 'ValueObjects',
        'aggregates'     => 'Aggregates',
        'events'         => 'Events',
        'exceptions'     => 'Exceptions',
        'factories'      => 'Factories',
        'repositories'   => 'Repositories',
        'services'       => 'Services',
        'policies'       => 'Policies',
        'specifications' => 'Specifications',
        'enums'          => 'Enums',
    ],

    // Auto-discover and register context ServiceProviders at boot
    'auto_discover' => true,

    // ServiceProvider class name pattern
    // Tokens: {namespace}, {context}
    'provider_pattern' => '{namespace}\\{context}\\Infrastructure\\Providers\\{context}ServiceProvider',

    // Stub search paths (highest priority first; package built-ins always last)
    'stub_paths' => [
        resource_path('stubs/ddd'),
        base_path('stubs/ddd'),
    ],

    // File writer options
    'generator' => [
        'backup_on_overwrite' => false,   // create .bak before overwriting
        'dry_run'             => false,   // preview without writing
    ],
];
```

---

## Example Application

The `example/` directory contains a complete working **Catalog** bounded context demonstrating the full DDD + CQRS stack:

```
example/
├── Shared/Domain/
│   ├── Contracts/  AggregateRootContract, EntityContract
│   └── ValueObjects/  Uuid
│
├── Domain/Catalog/
│   ├── Entities/       Product.php
│   ├── ValueObjects/   ProductName.php, Money.php
│   ├── Events/         ProductWasCreated.php, ProductPriceWasChanged.php
│   ├── Exceptions/     InvalidProductException.php
│   └── Repositories/   ProductRepositoryInterface.php
│
├── Application/Catalog/
│   ├── Commands/   CreateProductCommand.php
│   ├── Queries/    GetProductQuery.php
│   └── Handlers/   CreateProductHandler.php, GetProductQueryHandler.php
│
├── Infrastructure/Catalog/
│   ├── Persistence/
│   │   ├── Eloquent/      ProductModel.php
│   │   ├── Repositories/  EloquentProductRepository.php
│   │   └── Migrations/    2026_01_01_000000_create_catalog_products_table.php
│   └── Providers/         CatalogServiceProvider.php
│
└── Presentation/Http/
    ├── Controllers/Api/  ProductController.php
    ├── Requests/         StoreProductRequest.php
    ├── Resources/        ProductResource.php
    └── Routes/           api.php
```

### API endpoints

| Method | URL | Handler |
|---|---|---|
| `GET` | `/api/v1/products` | `ProductController@index` |
| `POST` | `/api/v1/products` | `ProductController@store` |
| `GET` | `/api/v1/products/{id}` | `ProductController@show` |

### Request body (POST)

```json
{
    "name": "Mechanical Keyboard",
    "price_amount": 14999,
    "price_currency": "USD"
}
```

### Response

```json
{
    "data": {
        "id": "550e8400-e29b-41d4-a716-446655440000",
        "name": "Mechanical Keyboard",
        "price": {
            "amount": 14999,
            "currency": "USD",
            "formatted": "USD 149.99"
        },
        "active": true,
        "created_at": "2026-01-01T00:00:00+00:00"
    }
}
```

---

## Testing

```bash
# Run the full suite
composer test

# Run only unit tests
vendor/bin/phpunit --testsuite=Unit

# Run only feature tests
vendor/bin/phpunit --testsuite=Feature

# Run with coverage
composer test:coverage

# Run code-style check
composer pint
```

### What is tested

**Unit tests**
- `StubRendererTest` — token replacement, custom stub resolution priority, missing stub exception
- `ContextResolverTest` — register/get/forget, path/namespace/provider resolution across all three modes
- `FileGeneratorTest` — create, skip, force-overwrite, dry-run, directory creation, `GenerationResult` states
- `GeneratorsTest` — all 14 generators: correct file path, correct namespace, correct class name, skip/force behaviour, mode-switching namespace correctness
- `ValueObjectTest` — `Uuid`, `Money`, `ProductName` invariant enforcement
- `ProductTest` — domain entity: creation event, price-change event, event queue clearing, equality, exception on zero price

**Feature tests**
- `ArtisanCommandTest` — 28 assertions across all 14 commands: file creation, correct content, `--force` overwrite, skip-without-force, context discovery display, info panel output

---

## FAQ

**Q: Do I need to register anything in `config/app.php`?**  
No. The package uses Laravel's package auto-discovery. The `DddArchitectServiceProvider` and `DddArchitect` facade are registered automatically when you install the package.

---

**Q: Can I use this with an existing Laravel application that already has code in `app/`?**  
Yes. Switch to `flat` or `modular` mode and point the paths at a subdirectory of `app/`. Nothing in the package touches your existing files.

---

**Q: How do I rename a layer (e.g. call "Application" "UseCases")?**  
Edit `config/ddd-architect.php`:
```php
'layers' => [
    'application' => 'UseCases',
    // ...
],
```
All generators will immediately use the new directory name.

---

**Q: I published a stub and edited it. Why is the package still using the built-in?**  
Check that the published stub is in a path listed in `config('ddd-architect.stub_paths')`. The default is `resource_path('stubs/ddd')`. Run `php artisan ddd:info` to see the resolution order and which paths exist.

---

**Q: Can I generate files without writing them (preview mode)?**  
Yes. Set `config('ddd-architect.generator.dry_run') = true`. All generators will report as if they succeeded but no files are written.

---

**Q: How do I disable the Shared Kernel scaffolding?**  
In `config/ddd-architect.php`:
```php
'shared_kernel' => [
    'auto_scaffold' => false,
],
```

---

**Q: How do I add a new generator (e.g. for Policies)?**  
See [Adding a custom generator](#adding-a-custom-generator) in the Extension Patterns section. You need to: create a stub file, extend `AbstractGenerator`, implement three methods, and bind the class in your service provider.

---

**Q: How do I stop auto-discovery from registering a specific context?**  
Either set `auto_discover => false` globally and register providers manually, or simply don't create a `{Context}ServiceProvider` class — auto-discovery only registers providers that actually exist as classes.

---

**Q: Does this work with PHP 8.1 readonly properties?**  
Yes. All generated stubs use `readonly` properties where appropriate and are fully compatible with PHP 8.1+.

---

**Q: Can I use a different UUID library (e.g. ramsey/uuid)?**  
Yes. The `Uuid` value object in the Shared Kernel is a generated file, not a package class. Publish the stubs and edit `resources/stubs/ddd/shared/uuid.stub` to wrap your preferred library. Existing generated files are not changed.

---

**Q: Is there a command bus / event bus included?**  
No — deliberately. The package generates the CQRS artefacts (Command, Query, Handler) but leaves the dispatch mechanism to you. You can wire handlers directly via `app(Handler::class)->handle($command)`, use Laravel's built-in `Bus::dispatch()`, or integrate any command bus library you prefer.

---

## Licence

MIT © [Archify](https://archify.dev)
