# AutoERP Configuration-Driven Architecture

## Overview

This document outlines the metadata-driven, runtime-configurable system architecture that enables dynamic behavior without code modifications, as required by the AutoERP specification.

**Last Updated:** 2026-02-19

---

## Core Principles

1. **No Hardcoding** - All business logic, rules, pricing, workflows should be configurable
2. **Metadata-Driven** - UI, forms, reports, dashboards defined by metadata
3. **Runtime Configuration** - Changes take effect immediately without deployment
4. **Extensibility** - Easy to add new features without modifying core code
5. **Multi-Tenancy** - Each tenant can have custom configurations

---

## Configuration Layers

### 1. Application Configuration (.env)
**Purpose:** Infrastructure and environment-specific settings  
**Stored in:** `.env` file  
**Scope:** Global (all tenants)

```env
# Application
APP_NAME=AutoERP
APP_ENV=production
APP_DEBUG=false

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1

# Multi-Tenancy
TENANCY_DATABASE_PREFIX=tenant_
CENTRAL_DOMAINS=autoerp.com
```

**When to use:** Infrastructure settings that don't change per tenant

---

### 2. System Configuration (config/*.php)
**Purpose:** System-wide defaults and framework settings  
**Stored in:** `config/` directory  
**Scope:** Global (all tenants)

```php
// config/pricing.php
return [
    'default_strategy' => env('PRICING_DEFAULT_STRATEGY', 'flat'),
    'enable_volume_discounts' => env('PRICING_VOLUME_DISCOUNTS', true),
    'tax_calculation_mode' => env('TAX_CALCULATION', 'inclusive'),
    'precision' => env('PRICING_PRECISION', 4),
];
```

**When to use:** Framework defaults, feature flags, system-wide settings

---

### 3. Organization Configuration (database)
**Purpose:** Organization-specific settings  
**Stored in:** `organization_settings` table  
**Scope:** Per organization

```sql
CREATE TABLE organization_settings (
    id BIGINT PRIMARY KEY,
    organization_id BIGINT NOT NULL,
    setting_group VARCHAR(100) NOT NULL, -- e.g., 'pricing', 'inventory', 'billing'
    setting_key VARCHAR(100) NOT NULL,
    setting_value JSON NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    UNIQUE(organization_id, setting_group, setting_key)
);
```

Example usage:
```php
// Get organization setting
$setting = OrganizationSetting::where('organization_id', $orgId)
    ->where('setting_group', 'pricing')
    ->where('setting_key', 'default_currency')
    ->first();

// Returns: ['currency_code' => 'USD', 'decimal_places' => 2]
$value = $setting->setting_value;
```

**When to use:** Tenant-specific business settings

---

### 4. Metadata-Driven UI (database)
**Purpose:** Dynamic forms, fields, validations, workflows  
**Stored in:** `metadata_*` tables  
**Scope:** Per module/feature

#### Form Definitions
```sql
CREATE TABLE metadata_forms (
    id BIGINT PRIMARY KEY,
    organization_id BIGINT,
    module VARCHAR(50),
    form_name VARCHAR(100),
    metadata JSON NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Example metadata JSON
{
    "form_id": "product_create",
    "title": "Create Product",
    "fields": [
        {
            "name": "name",
            "type": "text",
            "label": "Product Name",
            "required": true,
            "validation": ["required", "max:255"]
        },
        {
            "name": "category_id",
            "type": "select",
            "label": "Category",
            "required": true,
            "options_source": "api:/api/v1/product-categories",
            "validation": ["required", "exists:product_categories,id"]
        },
        {
            "name": "price",
            "type": "number",
            "label": "Price",
            "required": true,
            "decimal_places": 2,
            "validation": ["required", "numeric", "min:0"]
        }
    ]
}
```

#### Field Definitions
```sql
CREATE TABLE metadata_fields (
    id BIGINT PRIMARY KEY,
    organization_id BIGINT,
    entity VARCHAR(100), -- e.g., 'product', 'customer'
    field_name VARCHAR(100),
    field_type VARCHAR(50), -- text, number, date, select, checkbox, etc.
    field_config JSON NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Example field_config JSON
{
    "label": "Tax Exempt",
    "type": "checkbox",
    "default_value": false,
    "required": false,
    "visible_in_list": true,
    "visible_in_form": true,
    "editable": true,
    "validation": {
        "type": "boolean"
    }
}
```

---

### 5. Business Rules Engine (database)
**Purpose:** Dynamic business logic and validations  
**Stored in:** `business_rules` table  
**Scope:** Per module/feature

```sql
CREATE TABLE business_rules (
    id BIGINT PRIMARY KEY,
    organization_id BIGINT,
    module VARCHAR(50),
    rule_name VARCHAR(100),
    rule_type VARCHAR(50), -- validation, calculation, workflow, notification
    priority INT DEFAULT 0,
    conditions JSON NOT NULL,
    actions JSON NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Example: Minimum order quantity rule
{
    "rule_name": "minimum_order_quantity",
    "rule_type": "validation",
    "conditions": {
        "entity": "order_item",
        "when": {
            "product.category": "wholesale",
            "quantity": "<10"
        }
    },
    "actions": {
        "error": "Minimum order quantity for wholesale is 10 units"
    }
}

-- Example: Automatic discount rule
{
    "rule_name": "bulk_discount",
    "rule_type": "calculation",
    "conditions": {
        "entity": "order",
        "when": {
            "total_quantity": ">=100"
        }
    },
    "actions": {
        "apply_discount": {
            "type": "percentage",
            "value": 10,
            "description": "10% bulk discount"
        }
    }
}
```

---

### 6. Workflow Engine (database)
**Purpose:** Configurable approval workflows and processes  
**Stored in:** `workflows` table  
**Scope:** Per module/feature

```sql
CREATE TABLE workflows (
    id BIGINT PRIMARY KEY,
    organization_id BIGINT,
    workflow_name VARCHAR(100),
    entity VARCHAR(100), -- e.g., 'purchase_order', 'invoice'
    metadata JSON NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

CREATE TABLE workflow_steps (
    id BIGINT PRIMARY KEY,
    workflow_id BIGINT,
    step_order INT,
    step_type VARCHAR(50), -- approval, notification, calculation, etc.
    step_config JSON NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Example workflow metadata
{
    "workflow_name": "Purchase Order Approval",
    "entity": "purchase_order",
    "trigger": "on_create",
    "steps": [
        {
            "order": 1,
            "type": "approval",
            "config": {
                "approver_role": "manager",
                "condition": "total_amount > 1000",
                "timeout_hours": 24
            }
        },
        {
            "order": 2,
            "type": "notification",
            "config": {
                "recipients": ["purchasing_department"],
                "template": "purchase_order_approved",
                "channels": ["email", "in_app"]
            }
        },
        {
            "order": 3,
            "type": "action",
            "config": {
                "action": "create_inventory_reservation",
                "params": {
                    "items": "{{purchase_order.items}}"
                }
            }
        }
    ]
}
```

---

### 7. Pricing Configuration (database)
**Purpose:** Dynamic pricing rules without code changes  
**Already implemented in Pricing module**

```php
// Price rules are stored in database
PriceRule::create([
    'name' => 'Volume Discount - Electronics',
    'priority' => 100,
    'conditions' => [
        'product_category' => 'electronics',
        'quantity_min' => 10,
    ],
    'actions' => [
        'discount_type' => 'percentage',
        'discount_value' => 15,
    ],
]);
```

---

### 8. Permission Configuration (database)
**Purpose:** Dynamic role and permission management  
**Already implemented via Spatie Laravel Permission**

```php
// Permissions are stored in database
Role::create(['name' => 'warehouse_manager'])
    ->givePermissionTo([
        'inventory.view',
        'inventory.create',
        'inventory.update',
        'stock_movements.create',
    ]);
```

---

## Implementation Strategy

### Phase 1: Core Infrastructure ✅ (Already Implemented)
- [x] `.env` configuration
- [x] `config/*.php` system configuration
- [x] Pricing rules (database-driven)
- [x] Permissions (database-driven)
- [x] Enums for type safety

### Phase 2: Metadata-Driven UI (To Implement)
- [ ] Create `metadata_forms` table and model
- [ ] Create `metadata_fields` table and model
- [ ] Create FormBuilder service
- [ ] Create API endpoints for form metadata
- [ ] Create Vue.js dynamic form renderer
- [ ] Create form metadata admin UI

### Phase 3: Business Rules Engine (To Implement)
- [ ] Create `business_rules` table and model
- [ ] Create RuleEngine service
- [ ] Implement condition evaluator
- [ ] Implement action executor
- [ ] Create API endpoints for rule management
- [ ] Create rule builder admin UI

### Phase 4: Workflow Engine (To Implement)
- [ ] Create `workflows` and `workflow_steps` tables
- [ ] Create `workflow_instances` table (runtime data)
- [ ] Create WorkflowEngine service
- [ ] Implement step processors (approval, notification, action)
- [ ] Create API endpoints for workflow management
- [ ] Create workflow designer UI

### Phase 5: Organization Settings (To Implement)
- [ ] Create `organization_settings` table and model
- [ ] Create SettingsService with caching
- [ ] Create API endpoints for settings management
- [ ] Create settings admin UI
- [ ] Integrate settings across modules

---

## Usage Examples

### Example 1: Dynamic Form Creation

**Admin creates a custom field:**
```php
POST /api/v1/metadata/fields
{
    "entity": "customer",
    "field_name": "loyalty_tier",
    "field_type": "select",
    "field_config": {
        "label": "Loyalty Tier",
        "options": ["Bronze", "Silver", "Gold", "Platinum"],
        "default_value": "Bronze",
        "required": false
    }
}
```

**Frontend dynamically renders the field:**
```vue
<template>
    <DynamicForm :entity="'customer'" @submit="handleSubmit" />
</template>

<script>
export default {
    components: { DynamicForm },
    // DynamicForm fetches metadata and renders fields dynamically
}
</script>
```

### Example 2: Business Rule Configuration

**Admin creates a validation rule:**
```php
POST /api/v1/business-rules
{
    "module": "sales",
    "rule_name": "Credit Limit Check",
    "rule_type": "validation",
    "conditions": {
        "entity": "order",
        "when": {
            "customer.credit_limit_enabled": true,
            "total_amount": "> customer.credit_limit"
        }
    },
    "actions": {
        "error": "Order exceeds customer credit limit",
        "suggest_action": "Request credit approval"
    }
}
```

**System automatically enforces the rule:**
```php
// In OrderService
public function create(array $data): Order
{
    // Rule engine automatically checks all active rules
    $this->ruleEngine->validate('order', $data);
    
    // If rule fails, ValidationException is thrown automatically
    return $this->repository->create($data);
}
```

### Example 3: Workflow Configuration

**Admin creates an approval workflow:**
```php
POST /api/v1/workflows
{
    "workflow_name": "Invoice Approval",
    "entity": "invoice",
    "trigger": "on_submit",
    "steps": [
        {
            "order": 1,
            "type": "approval",
            "config": {
                "approver_role": "finance_manager",
                "condition": "total_amount > 5000"
            }
        },
        {
            "order": 2,
            "type": "notification",
            "config": {
                "recipients": ["customer"],
                "template": "invoice_approved"
            }
        }
    ]
}
```

**System automatically executes workflow:**
```php
// In InvoiceService
public function submit(int $id): Invoice
{
    $invoice = $this->find($id);
    
    // Workflow engine automatically starts workflow
    $this->workflowEngine->start('invoice', $invoice);
    
    return $invoice;
}
```

---

## Benefits

### For Developers
- **Reduced Code Changes** - Business logic changes don't require code deployment
- **Consistency** - Unified approach to configuration across modules
- **Testability** - Rules and workflows can be tested independently
- **Extensibility** - Easy to add new rule types or workflow steps

### For Business Users
- **Self-Service** - Configure business rules without developer involvement
- **Flexibility** - Adapt to changing business needs quickly
- **Visibility** - See all rules and workflows in one place
- **Audit Trail** - Track changes to configuration over time

### For System
- **Performance** - Cached configuration for fast access
- **Scalability** - Configuration per tenant/organization
- **Security** - Permission-based access to configuration
- **Maintainability** - Centralized configuration management

---

## Security Considerations

1. **Access Control** - Only authorized users can modify configuration
2. **Validation** - All configuration changes are validated
3. **Audit Logging** - All changes are logged with user, timestamp, old/new values
4. **Versioning** - Configuration changes are versioned for rollback
5. **Isolation** - Tenant configuration is strictly isolated

---

## Implementation Guidelines

### Adding a New Configurable Feature

1. **Identify Configuration Needs**
   - What should be configurable?
   - Who should configure it?
   - What are the default values?

2. **Choose Configuration Layer**
   - Environment? → `.env`
   - System-wide? → `config/*.php`
   - Tenant-specific? → `organization_settings`
   - Dynamic? → Business rules or metadata

3. **Implement Configuration Model**
   - Create migration if needed
   - Create model with validation
   - Add API endpoints
   - Add admin UI

4. **Use Configuration in Code**
   ```php
   // BAD - hardcoded
   if ($amount > 1000) {
       // require approval
   }
   
   // GOOD - configurable
   $threshold = config('approval.threshold', 1000);
   if ($amount > $threshold) {
       // require approval
   }
   
   // BETTER - organization-specific
   $threshold = OrganizationSetting::get('approval.threshold', $orgId, 1000);
   if ($amount > $threshold) {
       // require approval
   }
   ```

5. **Add Tests**
   - Test default behavior
   - Test with custom configuration
   - Test configuration validation

6. **Document**
   - Add to configuration documentation
   - Add to user manual
   - Add example configurations

---

## Roadmap

### Q1 2026
- [x] Implement Pricing configuration (complete)
- [ ] Implement Organization Settings module
- [ ] Implement Metadata Forms (basic)

### Q2 2026
- [ ] Implement Business Rules Engine
- [ ] Implement Workflow Engine (basic)
- [ ] Create admin UI for configuration

### Q3 2026
- [ ] Enhanced Workflow Engine (complex workflows)
- [ ] Dynamic Reports & Dashboards
- [ ] Advanced Business Rules (AI/ML integration)

---

## Related Documentation

- [ARCHITECTURE.md](ARCHITECTURE.md) - Overall system architecture
- [MODULE_STATUS.md](MODULE_STATUS.md) - Module implementation status
- [PRICING Module README](Modules/Pricing/README.md) - Pricing configuration details
- [SECURITY.md](SECURITY.md) - Security best practices

---

**Note:** This is a living document. As we implement more configuration-driven features, this document will be updated to reflect the current state and best practices.
