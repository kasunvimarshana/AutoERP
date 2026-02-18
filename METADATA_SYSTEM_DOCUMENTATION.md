# Metadata-Driven UI System Documentation

## Overview

The AutoERP platform now features a fully metadata-driven, runtime-configurable UI system. This allows you to define entities, fields, menus, and workflows through configuration rather than hard-coded components.

## Architecture

### Backend Components

#### 1. Database Tables
- `metadata_entities` - Entity definitions (products, customers, etc.)
- `metadata_fields` - Field definitions with validation rules
- `metadata_workflows` - Workflow state machines
- `metadata_menus` - Hierarchical menu structure
- `metadata_feature_flags` - Feature toggles

#### 2. Models
All models include relationships, scopes, and helper methods:
- `MetadataEntity`
- `MetadataField`
- `MetadataWorkflow`
- `MetadataMenu`
- `MetadataFeatureFlag`

#### 3. Services
- `MetadataService` - Entity catalog and field configuration
- `MetadataMenuService` - Menu generation with permissions
- `FeatureFlagService` - Feature flag management

#### 4. API Endpoints

**Metadata Endpoints:**
```
GET  /api/v1/metadata/catalog              # Get all entities
GET  /api/v1/metadata/entity/{name}        # Get specific entity
GET  /api/v1/metadata/module/{module}      # Get entities by module
GET  /api/v1/metadata/entity/{name}/fields # Get field configuration
GET  /api/v1/metadata/entity/{name}/validation # Get validation rules
POST /api/v1/metadata/cache/clear          # Clear metadata cache
```

**Menu Endpoints:**
```
GET    /api/v1/menu            # Get user menu
GET    /api/v1/menu/all        # Get all menus (admin)
POST   /api/v1/menu            # Create menu item
PUT    /api/v1/menu/{id}       # Update menu item
DELETE /api/v1/menu/{id}       # Delete menu item
POST   /api/v1/menu/reorder    # Reorder menus
```

**Feature Flag Endpoints:**
```
GET  /api/v1/features                    # Get enabled features
GET  /api/v1/features/check/{name}       # Check specific feature
POST /api/v1/features/check-multiple     # Check multiple features
GET  /api/v1/features/module/{module}    # Get features by module
POST /api/v1/features/{name}/enable      # Enable feature
POST /api/v1/features/{name}/disable     # Disable feature
```

### Frontend Components

#### 1. State Management

**Metadata Store (Pinia)**
```javascript
import { useMetadataStore } from '@/stores/metadataStore';

const metadataStore = useMetadataStore();

// Initialize on app startup
await metadataStore.initialize();

// Get entity metadata
const entity = await metadataStore.fetchEntity('product');

// Get field config for forms
const fields = await metadataStore.fetchFieldConfig('product', 'form');

// Check feature flags
const isEnabled = metadataStore.isFeatureEnabled('advanced-reporting');
```

#### 2. Dynamic Components

**DynamicForm Component**
Renders forms entirely from metadata configuration.

```vue
<template>
  <DynamicForm
    :fields="fields"
    v-model="formData"
    submit-label="Save Product"
    @submit="handleSubmit"
    @cancel="handleCancel"
  />
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useMetadataStore } from '@/stores/metadataStore';
import DynamicForm from '@/components/common/DynamicForm.vue';

const metadataStore = useMetadataStore();
const fields = ref([]);
const formData = ref({});

onMounted(async () => {
  fields.value = await metadataStore.fetchFieldConfig('product', 'form');
});

const handleSubmit = (data) => {
  console.log('Form submitted:', data);
  // Save to API
};

const handleCancel = () => {
  console.log('Form cancelled');
};
</script>
```

**DynamicTable Component**
Renders tables entirely from metadata configuration.

```vue
<template>
  <DynamicTable
    :fields="fields"
    :data="products"
    :loading="loading"
    @create="handleCreate"
    @edit="handleEdit"
    @delete="handleDelete"
  >
    <!-- Custom cell slot example -->
    <template #cell-status="{ row }">
      <span :class="`badge badge-${row.status}`">
        {{ row.status }}
      </span>
    </template>
  </DynamicTable>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useMetadataStore } from '@/stores/metadataStore';
import DynamicTable from '@/components/common/DynamicTable.vue';

const metadataStore = useMetadataStore();
const fields = ref([]);
const products = ref([]);
const loading = ref(false);

onMounted(async () => {
  fields.value = await metadataStore.fetchFieldConfig('product', 'list');
  // Fetch products from API
});
</script>
```

#### 3. Permission Directives

**v-permission Directive**
Shows/hides elements based on user permissions.

```vue
<template>
  <!-- Single permission -->
  <button v-permission="'products.create'">Add Product</button>

  <!-- Any permission (OR logic) -->
  <button v-permission="['products.edit', 'products.create']">Edit</button>

  <!-- All permissions (AND logic) -->
  <button v-permission.all="['products.edit', 'products.approve']">Approve</button>
</template>
```

**v-role Directive**
Shows/hides elements based on user roles.

```vue
<template>
  <!-- Single role -->
  <div v-role="'admin'">Admin Panel</div>

  <!-- Any role -->
  <div v-role="['admin', 'manager']">Management Dashboard</div>

  <!-- All roles -->
  <div v-role.all="['admin', 'super-admin']">System Settings</div>
</template>
```

**v-can Directive**
Shorthand for entity.action permissions.

```vue
<template>
  <!-- Shorthand syntax -->
  <button v-can:products="'create'">Add Product</button>

  <!-- Full syntax -->
  <button v-can="{ entity: 'products', action: 'edit' }">Edit</button>
</template>
```

#### 4. Permission Composable

**usePermissions Composable**
Programmatic permission checking in script.

```vue
<script setup>
import { usePermissions } from '@/composables/usePermissions';

const { hasPermission, can, isSuperAdmin, userPermissions } = usePermissions();

// Check permission
if (hasPermission.value('products.create')) {
  console.log('User can create products');
}

// Check entity action
if (can.value('products', 'edit')) {
  console.log('User can edit products');
}

// Check if super admin
if (isSuperAdmin.value) {
  console.log('User is super admin');
}

// Get all user permissions
console.log('User permissions:', userPermissions.value);
</script>
```

## Usage Examples

### 1. Creating a Metadata-Driven List/Form View

```vue
<template>
  <div class="entity-view">
    <h1>{{ entityLabel }}</h1>

    <!-- Table View -->
    <DynamicTable
      v-if="!showForm"
      :fields="listFields"
      :data="items"
      :loading="loading"
      @create="showCreateForm"
      @edit="showEditForm"
      @delete="handleDelete"
    />

    <!-- Form View -->
    <DynamicForm
      v-if="showForm"
      :fields="formFields"
      v-model="formData"
      @submit="handleSubmit"
      @cancel="hideForm"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useMetadataStore } from '@/stores/metadataStore';
import DynamicTable from '@/components/common/DynamicTable.vue';
import DynamicForm from '@/components/common/DynamicForm.vue';

const props = defineProps({
  entityName: {
    type: String,
    required: true
  }
});

const metadataStore = useMetadataStore();

const entity = ref(null);
const listFields = ref([]);
const formFields = ref([]);
const items = ref([]);
const formData = ref({});
const showForm = ref(false);
const loading = ref(false);

const entityLabel = computed(() => entity.value?.label_plural || '');

onMounted(async () => {
  // Fetch entity metadata
  entity.value = await metadataStore.fetchEntity(props.entityName);
  
  // Fetch field configurations
  listFields.value = await metadataStore.fetchFieldConfig(props.entityName, 'list');
  formFields.value = await metadataStore.fetchFieldConfig(props.entityName, 'form');
  
  // Fetch data
  await loadData();
});

const loadData = async () => {
  loading.value = true;
  try {
    // Call your API service to fetch items
    // items.value = await yourService.getAll();
  } finally {
    loading.value = false;
  }
};

const showCreateForm = () => {
  formData.value = {};
  showForm.value = true;
};

const showEditForm = (item) => {
  formData.value = { ...item };
  showForm.value = true;
};

const hideForm = () => {
  showForm.value = false;
  formData.value = {};
};

const handleSubmit = async (data) => {
  // Save to API
  console.log('Saving:', data);
  hideForm();
  await loadData();
};

const handleDelete = async (item) => {
  if (confirm('Are you sure?')) {
    // Delete via API
    console.log('Deleting:', item);
    await loadData();
  }
};
</script>
```

### 2. Registering Directives in Main App

```javascript
// src/main.js
import { createApp } from 'vue';
import App from './App.vue';
import permissionDirectives from './directives/permissions';

const app = createApp(App);

// Register permission directives globally
app.directive('permission', permissionDirectives.permission);
app.directive('role', permissionDirectives.role);
app.directive('can', permissionDirectives.can);

app.mount('#app');
```

### 3. Initializing Metadata on App Startup

```javascript
// src/App.vue
<script setup>
import { onMounted } from 'vue';
import { useMetadataStore } from '@/stores/metadataStore';

const metadataStore = useMetadataStore();

onMounted(async () => {
  // Initialize metadata on app startup
  await metadataStore.initialize();
});
</script>
```

## Field Configuration Schema

### Field Definition Example

```json
{
  "name": "product_name",
  "label": "Product Name",
  "type": "text",
  "required": true,
  "is_searchable": true,
  "is_sortable": true,
  "is_visible_list": true,
  "is_visible_form": true,
  "order": 1,
  "validation": ["required", "min:3", "max:255"],
  "ui_config": {
    "placeholder": "Enter product name",
    "help": "The name of the product as it will appear to customers",
    "width": "200px"
  }
}
```

### Supported Field Types

- `text` - Single-line text input
- `textarea` - Multi-line text input
- `email` - Email input with validation
- `password` - Password input (masked)
- `number` - Numeric input
- `date` - Date picker
- `datetime` - Date and time picker
- `select` - Dropdown selection
- `multiselect` - Multiple selection dropdown
- `checkbox` - Boolean checkbox
- `radio` - Radio button group
- `file` - File upload
- `image` - Image upload with preview

## Menu Configuration

### Menu Item Structure

```json
{
  "name": "products",
  "label": "Products",
  "icon": "package",
  "route": "/inventory/products",
  "entity_name": "product",
  "permission": "products.view",
  "order": 10,
  "is_active": true
}
```

### Hierarchical Menu Example

```
Dashboard (/)
Inventory (null)
  └─ Products (/inventory/products)
  └─ Stock Ledger (/inventory/stock-ledger)
  └─ Warehouses (/inventory/warehouses)
CRM (null)
  └─ Customers (/crm/customers)
```

## Best Practices

1. **Always fetch metadata on component mount** - Ensures latest configuration
2. **Use permission directives** for UI element visibility
3. **Cache metadata** - Metadata store handles caching automatically
4. **Validate on submit** - DynamicForm validates based on metadata
5. **Use entity names consistently** - Match backend entity definitions
6. **Leverage custom slots** - Override default rendering when needed
7. **Handle loading states** - Show loading indicators during metadata fetch

## Security Considerations

- All API endpoints require authentication
- Permission checks happen on both frontend and backend
- Super admin role bypasses all permission checks
- Feature flags can enable/disable functionality tenant-wide
- Menu items respect user permissions automatically

## Performance Optimization

- Metadata is cached in Pinia store
- Field configurations are loaded on demand
- Menu structure is loaded once on app init
- Feature flags are checked with caching
- API responses are cached for 1 hour (configurable)

## Troubleshooting

### Forms not rendering
- Check that entity name matches backend definition
- Verify field visibility flags (is_visible_form)
- Ensure fields have proper order values

### Tables empty
- Check field visibility flags (is_visible_list)
- Verify data is being fetched correctly
- Check console for API errors

### Permission directives not working
- Ensure directives are registered in main.js
- Verify user is authenticated
- Check permission slugs match backend

### Menu not showing
- Check user has required permissions
- Verify menu items are marked as active
- Ensure menu structure is properly seeded

## Future Enhancements

- Visual metadata editor UI
- Workflow designer
- Advanced field types (rich text, multi-file upload)
- Dynamic dashboard builder
- Tenant-specific field customization
- Field-level permissions
- Conditional field visibility
- Formula fields and calculated values
