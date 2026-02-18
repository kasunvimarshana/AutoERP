# Modules Directory

This directory contains all application modules following the modular architecture pattern.

## Module Structure

Each module should follow this structure:

```
ModuleName/
├── Controllers/
├── Models/
├── Services/
├── Repositories/
├── DTOs/
├── Events/
├── Listeners/
├── Policies/
├── Requests/
├── Resources/
├── database/
│   ├── migrations/
│   ├── seeders/
│   └── factories/
├── routes/
│   ├── api.php
│   └── web.php
├── resources/
│   └── views/
├── tests/
│   ├── Feature/
│   └── Unit/
└── Providers/
    └── ModuleNameServiceProvider.php
```

## Creating a New Module

1. Create a new directory with your module name (PascalCase)
2. Set up the directory structure as shown above
3. Create a service provider for your module
4. The `ModuleServiceProvider` will automatically register your module

## Module Service Provider Example

```php
<?php

namespace Modules\ModuleName\Providers;

use Illuminate\Support\ServiceProvider;

class ModuleNameServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register module services
    }

    public function boot(): void
    {
        // Bootstrap module resources
    }
}
```

## Module Guidelines

- Each module should be self-contained and independent
- Follow clean architecture principles (Services, Repositories, DTOs)
- Use dependency injection
- Write tests for your module
- Document your module's API endpoints
- Use proper namespacing: `Modules\ModuleName\...`
