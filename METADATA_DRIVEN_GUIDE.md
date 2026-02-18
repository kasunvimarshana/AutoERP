# Metadata-Driven Architecture Guide

## Overview

AutoERP implements a comprehensive metadata-driven architecture that eliminates hardcoded business logic in the frontend. All UI elements, routes, forms, tables, navigation, and permissions are dynamically generated from backend metadata configuration.

## Table of Contents

1. [Core Concepts](#core-concepts)
2. [Metadata Structure](#metadata-structure)
3. [Dynamic Routing](#dynamic-routing)
4. [Dynamic Navigation](#dynamic-navigation)
5. [Dynamic Forms](#dynamic-forms)
6. [Field Operations](#field-operations)
7. [Feature Flags](#feature-flags)
8. [Permissions](#permissions)
9. [Best Practices](#best-practices)
10. [Examples](#examples)

---

## Core Concepts

### Zero Hardcoded Business Logic

The frontend contains **zero hardcoded business logic**. All business rules, validations, and workflows are defined in backend metadata and consumed dynamically by the frontend.

**Benefits:**
- ✅ Add new entities without frontend code changes
- ✅ Modify forms/tables through configuration
- ✅ Toggle features without deployments
- ✅ Tenant-specific customizations
- ✅ A/B testing capabilities
- ✅ Reduced technical debt

### Metadata-First Development

When adding new features:
1. Define entity metadata (entities, fields, permissions)
2. Configure navigation and routes
3. Set up workflows and validations
4. Generic components render everything automatically

---

## Metadata Structure

### Entity Metadata

```javascript
{
  name: 'product',
  label: 'Product',
  label_plural: 'Products',
  table_name: 'products',
  icon: 'package',
  module: 'inventory',
  description: 'Products and inventory items',
  is_system: false,
  is_active: true,
  has_workflow: true,
  has_audit_trail: true,
  is_tenant_scoped: true,
  ui_config: {
    list_page_size: 20,
    enable_bulk_actions: true,
    enable_export: true,
    enable_import: true,
    default_sort: 'name',
    default_sort_direction: 'asc'
  },
  api_config: {
    base_path: '/api/v1/inventory/products',
    supports_pagination: true,
    supports_search: true,
    supports_filtering: true
  },
  permissions: {
    view: 'products.view',
    create: 'products.create',
    edit: 'products.edit',
    delete: 'products.delete'
  }
}
```

### Field Metadata

```javascript
{
  name: 'name',
  label: 'Product Name',
  type: 'text',
  column_name: 'name',
  description: 'Display name of the product',
  is_required: true,
  is_unique: false,
  is_searchable: true,
  is_filterable: true,
  is_sortable: true,
  is_visible_list: true,
  is_visible_detail: true,
  is_visible_form: true,
  is_readonly: false,
  is_system: false,
  order: 2,
  validation_rules: ['required', 'string', 'max:255'],
  ui_config: {
    placeholder: 'Enter product name',
    help: 'Name as displayed to customers',
    width: '250px'
  },
  // Optional: Conditional visibility
  conditions: {
    field: 'type',
    operator: 'equals',
    value: 'inventory'
  },
  // Optional: Field-level permissions
  permissions: {
    view: 'products.view',
    edit: 'products.edit'
  }
}
```

---

## Dynamic Routing

### Automatic Route Generation

Routes are automatically generated from entity metadata:

```javascript
// No manual route definition needed!
// These are generated automatically:

GET  /inventory/products           → List view
GET  /inventory/products/create    → Create form
GET  /inventory/products/:id       → Detail view
GET  /inventory/products/:id/edit  → Edit form
```

### Using Dynamic Routes Service

```javascript
import { loadDynamicRoutes, addDynamicRoutes } from '@/services/dynamicRoutesService';

// In router setup
const dynamicRoutes = await loadDynamicRoutes();
addDynamicRoutes(router, dynamicRoutes);
```

### Route Metadata

Each generated route includes metadata:

```javascript
{
  path: '/inventory/products',
  name: 'product_list',
  component: GenericEntityView,
  meta: {
    requiresAuth: true,
    requiresPermission: 'products.view',
    entity: 'product',
    viewType: 'list',
    title: 'Products',
    breadcrumb: [
      { label: 'Home', path: '/' },
      { label: 'Inventory', path: '/inventory' },
      { label: 'Products', path: '/inventory/products' }
    ]
  }
}
```

---

## Dynamic Navigation

### Automatic Menu Generation

Navigation menus are generated from entity metadata with permission filtering:

```vue
<template>
  <DynamicSidebar 
    source="entities"
    :enable-search="true"
  />
</template>
```

### Navigation Service

```javascript
import { loadNavigation } from '@/services/dynamicNavigationService';

// Load navigation
const navigation = await loadNavigation('entities');

// Search navigation
const results = searchNavigation(navigation, 'product');

// Get breadcrumbs
const breadcrumbs = getBreadcrumbFromNavigation(navigation, '/inventory/products');
```

---

## Dynamic Forms

### Using DynamicForm

```vue
<template>
  <DynamicForm
    :fields="formFields"
    v-model="formData"
    @submit="handleSubmit"
    @cancel="handleCancel"
  />
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useMetadataStore } from '@/stores/metadataStore';
import DynamicForm from '@/components/common/DynamicForm.vue';

const metadataStore = useMetadataStore();
const formFields = ref([]);
const formData = ref({});

onMounted(async () => {
  // Fields are loaded from metadata
  formFields.value = await metadataStore.fetchFieldConfig('product', 'form');
});
</script>
```

### Supported Field Types

- `text` - Single-line text
- `textarea` - Multi-line text
- `email` - Email with validation
- `password` - Password (masked)
- `number` - Numeric input
- `date` - Date picker
- `datetime` - Date and time picker
- `select` - Dropdown
- `multiselect` - Multiple selection
- `checkbox` - Boolean
- `radio` - Radio buttons
- `file` - File upload
- `image` - Image upload
- `richtext` - Rich text editor

---

## Field Operations

### useFieldOperations Composable

```javascript
import { useFieldOperations } from '@/composables/useFieldOperations';

const { 
  visibleFields,          // Fields visible in current context
  fieldsBySection,        // Fields grouped by sections
  requiredFields,         // Required fields only
  editableFields,         // Editable fields only
  isFieldVisible,         // Check field visibility
  isFieldEditable,        // Check field editability
  hasFieldPermission,     // Check field permission
  formatFieldValue        // Format value for display
} = useFieldOperations(fields, formData, 'form');
```

### Conditional Field Visibility

Fields can be shown/hidden based on other field values:

```javascript
// Simple condition
{
  conditions: {
    field: 'type',
    operator: 'equals',
    value: 'inventory'
  }
}

// Multiple conditions (AND logic)
{
  conditions: [
    { field: 'type', operator: 'equals', value: 'inventory' },
    { field: 'track_stock', operator: 'equals', value: true }
  ],
  condition_logic: 'AND'
}

// Multiple conditions (OR logic)
{
  conditions: [
    { field: 'type', operator: 'equals', value: 'inventory' },
    { field: 'type', operator: 'equals', value: 'bundle' }
  ],
  condition_logic: 'OR'
}
```

### Supported Operators

- `equals` / `==` - Exact match
- `not_equals` / `!=` - Not equal
- `in` - Value in array
- `not_in` - Value not in array
- `greater_than` / `>` - Greater than
- `less_than` / `<` - Less than
- `greater_than_or_equal` / `>=` - Greater or equal
- `less_than_or_equal` / `<=` - Less or equal
- `contains` - String contains
- `not_contains` - String doesn't contain
- `is_empty` - Field is empty
- `is_not_empty` - Field has value
- `matches` - Regex match

---

## Feature Flags

### Using Feature Flags

```vue
<template>
  <!-- Directive approach -->
  <div v-feature="'advanced-reporting'">
    Advanced reporting features
  </div>

  <!-- Multiple features (any) -->
  <div v-feature="['feature1', 'feature2']">
    Shows if any feature is enabled
  </div>

  <!-- Multiple features (all) -->
  <div v-feature.all="['feature1', 'feature2']">
    Shows only if all features are enabled
  </div>
</template>

<script setup>
import { useFeatureFlags } from '@/composables/useFeatureFlags';

const { 
  isEnabled,              // Check single feature
  isAllEnabled,           // Check multiple (all)
  isAnyEnabled,           // Check multiple (any)
  isModuleEnabled,        // Check module
  enabledFeatures,        // Get all enabled
  enableFeature,          // Enable feature
  disableFeature          // Disable feature
} = useFeatureFlags();

// Check feature
if (isEnabled.value('advanced-reporting')) {
  // Feature is enabled
}

// Check module
if (isModuleEnabled.value('manufacturing')) {
  // Manufacturing module is enabled
}
</script>
```

### Feature Flag Configuration

```javascript
{
  name: 'advanced-reporting',
  is_enabled: true,
  value: { max_reports: 100 },
  metadata: {
    module: 'reporting',
    description: 'Advanced reporting capabilities'
  }
}
```

---

## Permissions

### Permission-Based Rendering

```vue
<template>
  <!-- Single permission -->
  <button v-permission="'products.create'">
    Create Product
  </button>

  <!-- Multiple permissions (any) -->
  <button v-permission="['products.edit', 'products.create']">
    Edit
  </button>

  <!-- Multiple permissions (all) -->
  <button v-permission.all="['products.edit', 'products.approve']">
    Approve
  </button>

  <!-- Role-based -->
  <div v-role="'admin'">
    Admin Panel
  </div>

  <!-- Entity action shorthand -->
  <button v-can:products="'create'">
    Create Product
  </button>
</template>
```

### usePermissions Composable

```javascript
import { usePermissions } from '@/composables/usePermissions';

const { 
  hasPermission,          // Check permission
  can,                    // Check entity action
  isSuperAdmin,           // Check super admin
  userPermissions         // All permissions
} = usePermissions();

// Check permission
if (hasPermission.value('products.create')) {
  // User can create products
}

// Check entity action
if (can.value('products', 'edit')) {
  // User can edit products
}
```

---

## Best Practices

### 1. Always Use Metadata

❌ **Don't:**
```vue
<template>
  <div v-if="userRole === 'admin'">Admin content</div>
</template>
```

✅ **Do:**
```vue
<template>
  <div v-permission="'admin.access'">Admin content</div>
</template>
```

### 2. Leverage Conditional Fields

❌ **Don't:**
```vue
<input v-if="productType === 'inventory'" v-model="stockLevel" />
```

✅ **Do:**
```javascript
// In metadata
{
  name: 'stock_level',
  conditions: {
    field: 'type',
    operator: 'equals',
    value: 'inventory'
  }
}
```

### 3. Use Feature Flags for Rollouts

❌ **Don't:**
```vue
<div v-if="isProduction">New Feature</div>
```

✅ **Do:**
```vue
<div v-feature="'new-feature'">New Feature</div>
```

### 4. Field-Level Permissions

❌ **Don't:**
```vue
<input v-if="canEditPrice" v-model="price" />
```

✅ **Do:**
```javascript
// In field metadata
{
  name: 'price',
  permissions: {
    view: 'products.view',
    edit: 'products.edit_price'
  }
}
```

### 5. Generic Over Specific

❌ **Don't:** Create separate components for each entity

✅ **Do:** Use GenericEntityView for all entities

---

## Examples

### Example 1: Adding a New Entity

```javascript
// 1. Create metadata (backend)
MetadataEntity::create([
  'name' => 'project',
  'label' => 'Project',
  'label_plural' => 'Projects',
  'module' => 'project_management',
  'permissions' => [
    'view' => 'projects.view',
    'create' => 'projects.create',
    'edit' => 'projects.edit',
    'delete' => 'projects.delete'
  ]
]);

// 2. Define fields
MetadataField::create([
  'entity_id' => $project->id,
  'name' => 'name',
  'label' => 'Project Name',
  'type' => 'text',
  'is_required' => true,
  'is_visible_list' => true,
  'is_visible_form' => true,
  'order' => 1
]);

// 3. That's it! Frontend automatically:
// - Generates routes
// - Adds to navigation
// - Renders forms and tables
// - Enforces permissions
```

### Example 2: Conditional Fields

```javascript
// Product type-specific fields
[
  {
    name: 'type',
    type: 'select',
    options: [
      { value: 'inventory', label: 'Inventory' },
      { value: 'service', label: 'Service' }
    ]
  },
  {
    name: 'stock_level',
    label: 'Stock Level',
    type: 'number',
    conditions: {
      field: 'type',
      operator: 'equals',
      value: 'inventory'
    }
  },
  {
    name: 'service_duration',
    label: 'Service Duration',
    type: 'number',
    conditions: {
      field: 'type',
      operator: 'equals',
      value: 'service'
    }
  }
]
```

### Example 3: Feature-Gated Module

```vue
<template>
  <div v-feature="'manufacturing.enabled'">
    <h2>Manufacturing</h2>
    
    <router-link
      v-feature="'manufacturing.bom'"
      to="/manufacturing/boms"
    >
      Bill of Materials
    </router-link>
    
    <router-link
      v-feature="'manufacturing.production_orders'"
      to="/manufacturing/production-orders"
    >
      Production Orders
    </router-link>
  </div>
</template>
```

---

## Summary

The metadata-driven architecture provides:

✅ **Zero Hardcoded Logic** - Everything from configuration
✅ **Permission-Aware** - Automatic permission enforcement
✅ **Feature Toggles** - Runtime feature control
✅ **Conditional UI** - Dynamic field visibility
✅ **Tenant Customization** - Per-tenant configurations
✅ **Rapid Development** - Add features without frontend code
✅ **Maintainability** - Single source of truth
✅ **Scalability** - Handles any number of entities

For more details, see:
- [API Documentation](./API_DOCUMENTATION.md)
- [Architecture](./ARCHITECTURE.md)
- [Implementation Guide](./IMPLEMENTATION_GUIDE.md)
