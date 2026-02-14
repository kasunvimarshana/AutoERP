# Vue.js Frontend Developer Guide

## Quick Start

### Development
```bash
npm run dev
```

### Production Build
```bash
npm run build
```

## Project Structure

```
resources/js/
├── components/       # Reusable UI components
├── layouts/         # Layout components (Auth, Main)
├── modules/         # Feature modules
│   ├── auth/        # Authentication
│   ├── customers/   # Customer management
│   ├── products/    # Product catalog
│   ├── inventory/   # Stock management
│   └── ...         # Other modules
├── router/          # Vue Router configuration
├── services/        # API service layer
└── stores/          # Pinia stores
```

## Core Concepts

### API Services
All API calls go through service modules in `services/`:

```javascript
import * as customerService from '@/services/customer';

// Usage
const customers = await customerService.getCustomers({ page: 1 });
```

### State Management
Use Pinia stores for state management:

```javascript
import { useCustomerStore } from '@/stores/customer';

const customerStore = useCustomerStore();
await customerStore.fetchCustomers();
```

### Components

#### DataTable
Reusable table component with pagination:

```vue
<DataTable
  :columns="columns"
  :data="items"
  :loading="loading"
  :pagination="pagination"
  @page-change="handlePageChange"
>
  <template #cell-name="{ row }">
    {{ row.name }}
  </template>
  <template #actions="{ row }">
    <button @click="edit(row.id)">Edit</button>
  </template>
</DataTable>
```

#### Modal
Dialog component with slots:

```vue
<Modal v-model="showModal" title="Confirm Action">
  <p>Modal content here</p>
  <template #footer>
    <Button @click="confirm">Confirm</Button>
  </template>
</Modal>
```

#### Button
Styled button with variants:

```vue
<Button variant="primary" :loading="loading" @click="save">
  Save
</Button>
```

### Routing

Protected routes require authentication:

```javascript
{
  path: '/customers',
  component: CustomerList,
  meta: { requiresAuth: true }
}
```

Guest routes redirect authenticated users:

```javascript
{
  path: '/login',
  component: Login,
  meta: { guest: true }
}
```

## Adding a New Module

1. Create module directory:
```bash
mkdir -p resources/js/modules/mymodule/views
```

2. Create store (if needed):
```bash
touch resources/js/stores/mymodule.js
```

3. Create service:
```bash
touch resources/js/services/mymodule.js
```

4. Add routes in `router/index.js`

5. Create views in `modules/mymodule/views/`

## Best Practices

### 1. Use Composition API
```javascript
import { ref, computed, onMounted } from 'vue';

const count = ref(0);
const double = computed(() => count.value * 2);

onMounted(() => {
  // Initialize
});
```

### 2. Handle Loading States
```javascript
const loading = ref(false);

const fetchData = async () => {
  loading.value = true;
  try {
    await api.getData();
  } finally {
    loading.value = false;
  }
};
```

### 3. Handle Errors
```javascript
const error = ref('');

try {
  await store.save(data);
} catch (err) {
  error.value = err.response?.data?.message || 'An error occurred';
}
```

### 4. Form Validation
```javascript
const errors = ref({});

if (err.response?.data?.errors) {
  errors.value = err.response.data.errors;
}
```

## Styling

Using Tailwind CSS utility classes:

```vue
<div class="bg-white shadow rounded-lg p-6">
  <h1 class="text-2xl font-bold text-gray-900">Title</h1>
</div>
```

## Authentication

Check authentication status:

```javascript
import { useAuthStore } from '@/stores/auth';

const authStore = useAuthStore();
if (authStore.isAuthenticated) {
  // User is logged in
}
```

Get current user:

```javascript
const user = authStore.currentUser;
const tenant = authStore.currentTenant;
```

## API Configuration

Base URL is configured in `services/api.js`:

```javascript
baseURL: 'http://localhost:8000/api/v1'
```

All requests automatically include:
- Authorization header with token
- Content-Type: application/json
- Accept: application/json

## Common Patterns

### List View with Search and Pagination
```javascript
const items = ref([]);
const loading = ref(false);
const pagination = ref(null);
const searchQuery = ref('');

const loadItems = async (page = 1) => {
  loading.value = true;
  try {
    const response = await store.fetchItems({ page });
    items.value = response.data;
    pagination.value = response.meta;
  } finally {
    loading.value = false;
  }
};

onMounted(() => loadItems());
```

### Form with Validation
```javascript
const form = ref({
  name: '',
  email: '',
});

const errors = ref({});
const loading = ref(false);

const handleSubmit = async () => {
  loading.value = true;
  errors.value = {};
  
  try {
    await store.save(form.value);
    router.push('/list');
  } catch (err) {
    if (err.response?.data?.errors) {
      errors.value = err.response.data.errors;
    }
  } finally {
    loading.value = false;
  }
};
```

### Delete with Confirmation
```javascript
const showDeleteModal = ref(false);
const itemToDelete = ref(null);

const handleDelete = (id) => {
  itemToDelete.value = id;
  showDeleteModal.value = true;
};

const confirmDelete = async () => {
  await store.deleteItem(itemToDelete.value);
  showDeleteModal.value = false;
};
```

## Testing

Run tests (when configured):
```bash
npm run test
```

## Troubleshooting

### Build Errors
Clear cache and rebuild:
```bash
rm -rf node_modules/.vite
npm run build
```

### API Connection Issues
Check base URL in `services/api.js` and ensure backend is running on port 8000.

### Authentication Issues
Clear localStorage and re-login:
```javascript
localStorage.clear();
```
