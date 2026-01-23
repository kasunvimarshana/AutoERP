# Module Development Guide

## Overview

This guide explains how to create new modules in the Modular SaaS Vehicle Service application, following Clean Architecture principles and the Controller → Service → Repository pattern.

## Module Structure

Each module should follow this standard structure:

```
modules/{ModuleName}/
├── Controllers/          # HTTP request handlers
├── Services/            # Business logic layer
├── Repositories/        # Data access layer
├── Models/              # Eloquent models
├── Migrations/          # Database migrations
├── Requests/            # Form request validation
├── Policies/            # Authorization policies
├── Events/              # Domain events
├── Listeners/           # Event listeners
├── Resources/           # API resources (transformers)
├── Tests/               # Module-specific tests
└── README.md            # Module documentation
```

## Creating a New Module

### Step 1: Create Directory Structure

```bash
mkdir -p modules/YourModule/{Controllers,Services,Repositories,Models,Migrations,Requests,Policies,Events,Listeners,Resources,Tests}
```

### Step 2: Create the Model

```php
<?php

namespace Modules\YourModule\Models;

class YourEntity extends Model
{
    use SoftDeletes;

    protected $fillable = ['tenant_id', 'name'];
    protected $casts = ['created_at' => 'datetime'];

    // Tenant scope
    public function scopeTenant($query, $tenantId = null)
    {
        $tenantId = $tenantId ?? auth()->user()?->tenant_id;
        return $query->where('tenant_id', $tenantId);
    }
}
```

### Step 3: Create Repository, Service, Controller

See full examples in Customer and Vehicle modules.

## Cross-Module Interactions

Always inject other services via constructor and use transactions.

## Best Practices

1. Always use transactions for data modifications
2. Log all important activities
3. Fire events for asynchronous operations
4. Validate at service layer
5. Keep controllers thin
6. Write tests for all service methods

## Example Modules

- **Customer Module**: Basic CRUD operations
- **Vehicle Module**: Cross-module interactions, ownership transfer
