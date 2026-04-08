# Laravel DDD Architect

[![CI](https://github.com/yourvendor/laravel-ddd-architect/workflows/CI/badge.svg)](https://github.com/yourvendor/laravel-ddd-architect/actions)
[![Latest Version](https://img.shields.io/packagist/v/yourvendor/laravel-ddd-architect.svg)](https://packagist.org/packages/yourvendor/laravel-ddd-architect)
[![PHP Version](https://img.shields.io/packagist/php-v/yourvendor/laravel-ddd-architect.svg)](https://packagist.org/packages/yourvendor/laravel-ddd-architect)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE.md)

A **zero-dependency, fully customisable** Laravel package for scaffolding **Domain-Driven Design (DDD)** bounded-context structures with CQRS and a clean layered architecture — no third-party module libraries required.

---

## Table of Contents

- [Why This Package](#why-this-package)
- [Architecture Overview](#architecture-overview)
- [Requirements](#requirements)
- [Installation](#installation)
- [Quick Start](#quick-start)
- [Architecture Modes](#architecture-modes)
- [Artisan Commands](#artisan-commands)
- [Generated Structure](#generated-structure)
- [Shared Kernel](#shared-kernel)
- [Configuration Reference](#configuration-reference)
- [Customising Stubs](#customising-stubs)
- [Auto-Discovery](#auto-discovery)
- [Extending the Package](#extending-the-package)
- [Testing](#testing)
- [FAQ](#faq)

---

## Why This Package

Most Laravel applications eventually grow complex enough that the default MVC structure becomes a burden. `laravel-ddd-architect` gives you:

| Feature | This package | nWidart/laravel-modules |
|---|---|---|
| No third-party runtime dependency | ✅ | ❌ requires its own module system |
| Full DDD layer scaffolding | ✅ | ❌ basic only |
| CQRS Command/Query/Handler pairs | ✅ | ❌ |
| Composable Specifications | ✅ | ❌ |
| Shared Kernel (Money, Email, UUID) | ✅ | ❌ |
| Publish & customise stubs | ✅ | ✅ |
| Auto-discovers context providers | ✅ | ✅ |
| Configurable architecture modes | ✅ | ❌ |
| Pure PHP — no module manifest file | ✅ | ❌ |

---

## Architecture Overview

```
app/
├── Domain/                    # Pure business logic (framework-independent)
│   ├── Shared/                # Cross-cutting base classes & value objects
│   │   ├── Contracts/         # AggregateRoot, EntityContract, RepositoryContract
│   │   ├── Events/            # DomainEvent base class
│   │   ├── Exceptions/        # DomainException base class
│   │   └── ValueObjects/      # Uuid, Email, Money, AbstractValueObject
│   │
│   └── {Context}/             # e.g. Order, Billing, Identity
│       ├── Aggregates/        # Aggregate roots (consistency boundary)
│       ├── Entities/          # Domain entities with identity
│       ├── ValueObjects/      # Immutable domain value objects
│       ├── Repositories/      # Repository interfaces (contracts only)
│       ├── Services/          # Domain services (stateless, cross-entity logic)
│       ├── Events/            # Domain events (past-tense, immutable)
│       ├── Exceptions/        # Domain-specific exceptions
│       ├── Policies/          # Business-level authorisation rules
│       ├── Enums/             # Domain enumerations
│       ├── Factories/         # Complex domain object factories
│       └── Specifications/    # Composable business rule specifications
│
├── Application/               # Use-case orchestration (thin, no business logic)
│   ├── Shared/
│   └── {Context}/
│       ├── DTOs/              # Input/output data transfer objects
│       ├── UseCases/          # Single-responsibility use cases
│       ├── Commands/          # CQRS write-side command objects
│       ├── Queries/           # CQRS read-side query objects
│       ├── Handlers/          # Command & query handlers
│       ├── Mappers/           # DTO ↔ Domain mappers
│       ├── Validators/        # Application-level validators
│       └── Services/          # Application orchestration services
│
├── Infrastructure/            # Framework & persistence implementations
│   ├── Persistence/
│   │   ├── Eloquent/          # Eloquent ORM models (persistence only)
│   │   ├── Repositories/      # Eloquent repository implementations
│   │   ├── Migrations/        # Per-context database migrations
│   │   ├── Factories/         # Test data factories
│   │   ├── Seeders/           # Database seeders
│   │   └── Casts/             # Custom Eloquent attribute casts
│   ├── Services/              # External service adapters
│   ├── Integrations/          # Third-party API clients
│   ├── Events/                # Laravel event listeners/subscribers
│   ├── Jobs/                  # Queue jobs
│   ├── Notifications/         # Notification classes
│   ├── Providers/             # Context service providers (bindings)
│   └── Logging/               # Custom logging channels
│
└── Presentation/              # HTTP / CLI delivery layer
    ├── Http/
    │   ├── Controllers/Api/   # Thin JSON API controllers
    │   ├── Controllers/Web/   # Thin Blade web controllers
    │   ├── Requests/          # Form Request validation
    │   ├── Resources/         # API JSON transformers
    │   ├── Middleware/        # HTTP middleware
    │   ├── Exceptions/        # HTTP exception handling
    │   └── Routes/            # api.php and web.php per context
    ├── Console/Commands/      # Artisan commands
    └── Views/                 # Blade templates
```

---

## Requirements

| Requirement | Version |
|---|---|
| PHP | ^8.1 |
| Laravel | ^10.0 \| ^11.0 \| ^12.0 |

---

## Installation

```bash
composer require yourvendor/laravel-ddd-architect
```

Laravel's package auto-discovery registers the service provider automatically.

**Publish the configuration** (optional but recommended):

```bash
php artisan vendor:publish --tag=ddd-architect-config
```

---

## Quick Start

### 1. Scaffold a bounded context

```bash
php artisan ddd:make-context Order
```

This single command generates the complete DDD structure for the `Order` context across all four layers, plus the Shared Kernel base classes (on first run).

### 2. Register the context provider

**Laravel 11+ (`bootstrap/providers.php`)**:
```php
return [
    App\Domain\Order\Providers\OrderServiceProvider::class,
];
```

**Laravel 10 (`config/app.php`)**:
```php
'providers' => [
    App\Domain\Order\Providers\OrderServiceProvider::class,
],
```

### 3. Build your domain

```bash
# Add a domain entity
php artisan ddd:make-entity Order LineItem

# Add a value object
php artisan ddd:make-value-object Order OrderStatus

# Add a CQRS command + handler
php artisan ddd:make-command-handler Order CreateOrder

# Add a CQRS query + handler
php artisan ddd:make-query-handler Order GetOrderById

# Add a use case
php artisan ddd:make-use-case Order PlaceOrder

# Add a repository (interface + Eloquent implementation)
php artisan ddd:make-repository Order Order

# Add a domain service
php artisan ddd:make-domain-service Order PricingService

# Add a domain event
php artisan ddd:make-domain-event Order OrderWasPlaced

# Add an aggregate root
php artisan ddd:make-aggregate Order OrderAggregate

# Add a specification
php artisan ddd:make-specification Order OrderIsEligibleForDiscount
```

---

## Architecture Modes

Control how much structure is scaffolded via the `--mode` option or the `mode` config key.

| Mode | Layers Generated |
|---|---|
| `full` (default) | Domain + Application + Infrastructure + Presentation |
| `domain` | Domain only |
| `minimal` | Domain + Application |
| `custom` | Define your own layer list in `config/ddd-architect.php` |

```bash
# Generate only the Domain layer
php artisan ddd:make-context Payment --mode=domain

# Generate Domain + Application only
php artisan ddd:make-context Payment --mode=minimal
```

---

## Artisan Commands

| Command | Description |
|---|---|
| `ddd:make-context {name}` | Scaffold a complete bounded context |
| `ddd:make-entity {ctx} {name}` | Create a Domain Entity |
| `ddd:make-value-object {ctx} {name}` | Create an immutable Value Object |
| `ddd:make-aggregate {ctx} {name}` | Create an Aggregate Root |
| `ddd:make-use-case {ctx} {name}` | Create an Application Use Case |
| `ddd:make-repository {ctx} {name}` | Create Repository interface + Eloquent implementation |
| `ddd:make-domain-service {ctx} {name}` | Create a Domain Service |
| `ddd:make-domain-event {ctx} {name}` | Create a Domain Event |
| `ddd:make-command-handler {ctx} {name}` | Create a CQRS Command + Handler pair |
| `ddd:make-query-handler {ctx} {name}` | Create a CQRS Query + Handler pair |
| `ddd:make-dto {ctx} {name}` | Create a Data Transfer Object |
| `ddd:make-specification {ctx} {name}` | Create a composable Specification |
| `ddd:list` | List all discovered bounded contexts |
| `ddd:publish-stubs` | Publish stub templates for customisation |
| `ddd:info` | Display config and available commands |

### Global options (all `make:*` commands)

| Option | Description |
|---|---|
| `--force` | Overwrite existing files |

### `ddd:make-context` specific options

| Option | Description |
|---|---|
| `--mode=` | Override architecture mode for this context |
| `--no-shared` | Skip Shared Kernel scaffolding |
| `--no-tests` | Skip test directory scaffolding |

---

## Shared Kernel

On the first `ddd:make-context` run (unless `--no-shared` is passed), the package scaffolds a **Shared Kernel** at `app/Domain/Shared/` containing:

| File | Purpose |
|---|---|
| `Contracts/AggregateRoot.php` | Interface for aggregate roots (pullDomainEvents) |
| `Contracts/EntityContract.php` | Interface for entities (id, equals) |
| `Contracts/RepositoryContract.php` | Generic repository interface (findById, save, delete) |
| `Events/DomainEvent.php` | Abstract base for domain events |
| `Exceptions/DomainException.php` | Abstract base for domain exceptions |
| `ValueObjects/AbstractValueObject.php` | Abstract base for all value objects |
| `ValueObjects/Uuid.php` | UUID value object with format validation |
| `ValueObjects/Email.php` | Email value object with RFC validation |
| `ValueObjects/Money.php` | Money value object (minor-unit integer, ISO 4217 currency) |

These are plain PHP classes with zero framework dependency — they can be unit tested without booting Laravel.

---

## Configuration Reference

After publishing (`php artisan vendor:publish --tag=ddd-architect-config`), edit `config/ddd-architect.php`:

```php
return [
    'base_path'      => 'app',        // where app/ directories are created
    'namespace'      => 'App',         // root PSR-4 namespace
    'mode'           => 'full',        // full | domain | minimal | custom
    'shared_kernel'  => true,          // scaffold Shared/ on first context
    'auto_discover'  => true,          // auto-register context providers
    'generate_gitkeep' => true,        // add .gitkeep to empty directories

    // Customise which directories are created per layer
    'domain_structure'          => [...],
    'application_structure'     => [...],
    'infrastructure_structure'  => [...],
    'presentation_structure'    => [...],
    'shared_structure'          => [...],
];
```

---

## Customising Stubs

Publish the stubs once:

```bash
php artisan ddd:publish-stubs
```

All stubs land in `resources/stubs/ddd/`. The package **automatically prefers** your published stubs over its built-in ones — so any edit you make is respected immediately, with no config change required.

Available stub directories after publishing:

```
resources/stubs/ddd/
├── domain/
│   ├── entity.stub
│   ├── aggregate.stub
│   ├── value-object.stub
│   ├── repository-interface.stub
│   ├── domain-event.stub
│   ├── domain-service.stub
│   └── specification.stub
├── application/
│   ├── dto.stub
│   ├── use-case.stub
│   ├── command.stub
│   ├── command-handler.stub
│   ├── query.stub
│   └── query-handler.stub
├── infrastructure/
│   ├── eloquent-model.stub
│   ├── eloquent-repository.stub
│   ├── migration.stub
│   └── service-provider.stub
├── presentation/
│   ├── api-controller.stub
│   ├── form-request.stub
│   ├── api-resource.stub
│   ├── api-routes.stub
│   └── web-routes.stub
├── providers/
│   └── context-service-provider.stub
├── shared/
│   ├── aggregate-root.stub
│   ├── entity-contract.stub
│   ├── value-object.stub
│   ├── repository-contract.stub
│   ├── domain-event.stub
│   ├── domain-exception.stub
│   ├── uuid-value-object.stub
│   ├── email-value-object.stub
│   └── money-value-object.stub
└── tests/
    ├── unit-test.stub
    └── feature-test.stub
```

### Available stub tokens

| Token | Example value |
|---|---|
| `{{ namespace }}` | `App\Domain\Order\Entities` |
| `{{ rootNamespace }}` | `App` |
| `{{ contextName }}` | `Order` |
| `{{ contextLower }}` | `order` |
| `{{ contextSnake }}` | `order` |
| `{{ contextKebab }}` | `order` |
| `{{ className }}` | `LineItem` |
| `{{ classLower }}` | `lineitem` |
| `{{ classSnake }}` | `line_item` |
| `{{ classKebab }}` | `line-item` |
| `{{ year }}` | `2026` |
| `{{ date }}` | `2026_03_20` |
| `{{ timestamp }}` | `2026_03_20_123456` |

---

## Auto-Discovery

When `auto_discover` is `true` (default), the package scans `app/Domain/` on every request boot and automatically registers each context's `{Context}ServiceProvider` — so you don't need to manually add providers to `bootstrap/providers.php`.

To disable this behaviour (recommended for production performance):

```php
// config/ddd-architect.php
'auto_discover' => false,
```

Then register providers manually in `bootstrap/providers.php`.

---

## Extending the Package

### Custom Generator

Create your own generator by extending `AbstractGenerator`:

```php
use YourVendor\LaravelDDDArchitect\Generators\AbstractGenerator;

class MakePolicyGenerator extends AbstractGenerator
{
    public function __construct(
        array $config, $renderer, $files,
        private string $contextName,
        private string $policyName,
    ) {
        parent::__construct($config, $renderer, $files);
    }

    protected function stubs(): array
    {
        return [
            'domain/policy.stub' =>
                base_path("app/Domain/{$this->contextName}/Policies/{$this->policyName}Policy.php"),
        ];
    }

    protected function tokens(): array
    {
        return \YourVendor\LaravelDDDArchitect\Support\StubRenderer::buildTokens(
            context: $this->contextName,
            className: $this->policyName,
            layer: "Domain\\{$this->contextName}\\Policies",
        );
    }
}
```

### Custom Artisan Command

```php
use YourVendor\LaravelDDDArchitect\Commands\BaseCommand;

class MakePolicyCommand extends BaseCommand
{
    protected $signature = 'ddd:make-policy {context} {name}';
    protected $description = 'Create a Domain Policy';

    public function handle(): int
    {
        $generator = new MakePolicyGenerator(
            config: $this->config(),
            renderer: $this->renderer(),
            files: $this->fileGen(),
            contextName: $this->argument('context'),
            policyName: $this->argument('name'),
        );

        $this->reportCreated($generator->generate());
        return self::SUCCESS;
    }
}
```

Register in your `AppServiceProvider`:

```php
if ($this->app->runningInConsole()) {
    $this->commands([MakePolicyCommand::class]);
}
```

---

## Testing

```bash
# Run the full package test suite
composer test

# Run unit tests only
vendor/bin/phpunit --testsuite Unit

# Run feature tests only
vendor/bin/phpunit --testsuite Feature

# Run with coverage
vendor/bin/phpunit --coverage-html=coverage/
```

---

## FAQ

**Q: Does this replace `nWidart/laravel-modules`?**
No. It serves a different purpose. `nWidart/laravel-modules` gives you self-contained installable modules with their own `module.json` manifests. This package gives you a DDD-structured monolith within your existing `app/` directory, with no runtime dependency on any module system.

**Q: Can I use this alongside `nWidart/laravel-modules`?**
Yes. You can use this package to add DDD structure inside any nWidart module.

**Q: Does this enforce CQRS?**
No. It scaffolds CQRS-ready Command/Query/Handler files, but you are free to use simple Use Cases instead if CQRS is overkill for your project.

**Q: How do I add a new layer directory (e.g. `ReadModels/`)?**
Add it to `domain_structure` (or the relevant layer array) in `config/ddd-architect.php`. It will be created on the next `ddd:make-context` run.

**Q: Is this compatible with Event Sourcing?**
The Aggregate, DomainEvent, and AggregateRoot scaffolding is designed to be compatible with event-sourced aggregates. You would need to add your own event store and reconstitution logic.

---

## Licence

MIT — see [LICENSE.md](LICENSE.md).
