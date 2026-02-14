# Quick Start Guide - AutoERP Frontend

## Installation

```bash
# Navigate to frontend directory
cd frontend

# Install dependencies
npm install

# Copy environment file
cp .env.example .env

# Edit .env and configure your API URL
# VITE_API_BASE_URL=http://localhost:8000/api
```

## Development

```bash
# Start development server
npm run dev

# Open browser to http://localhost:5173
```

## Default Login (if backend has seeded data)
- Email: admin@example.com
- Password: password

## Project Overview

### Key Directories
- `src/modules/` - Feature modules (auth, crm, inventory, billing, appointments)
- `src/components/common/` - Reusable UI components
- `src/stores/` - State management (Pinia)
- `src/api/` - API client and endpoints

### Adding a New Feature

1. Create module: `src/modules/myfeature/views/MyFeatureListView.vue`
2. Add API endpoint: `src/api/myfeature.js`
3. Add route in `src/router/index.js`
4. Add navigation link in `src/components/layout/AppSidebar.vue`

### Common Components

```vue
<!-- Button -->
<BaseButton @click="handleClick" variant="primary">Save</BaseButton>

<!-- Input -->
<BaseInput v-model="form.name" label="Name" required />

<!-- Select -->
<BaseSelect v-model="form.status" :options="options" label="Status" />

<!-- Table -->
<BaseTable :columns="columns" :data="items" :loading="loading">
  <template #actions="{ row }">
    <button @click="edit(row.id)">Edit</button>
  </template>
</BaseTable>
```

### State Management

```javascript
import { useAuthStore } from '@stores/auth'

const authStore = useAuthStore()

// Login
await authStore.login({ email, password })

// Check permissions
if (authStore.hasPermission('customers.create')) {
  // Show create button
}

// Logout
authStore.logout()
```

### API Calls

```javascript
import api from '@/api'

// Fetch data
const response = await api.customers.getAll()
const customers = response.data

// Create
await api.customers.create(formData)

// Update
await api.customers.update(id, formData)

// Delete
await api.customers.delete(id)
```

## Building for Production

```bash
# Build
npm run build

# Preview production build
npm run preview
```

## Troubleshooting

### API Connection Issues
- Check VITE_API_BASE_URL in .env
- Ensure backend is running on correct port
- Check browser console for CORS errors

### Build Errors
- Clear node_modules and reinstall: `rm -rf node_modules package-lock.json && npm install`
- Check Node.js version: `node --version` (should be 18+)

### Style Issues
- Restart dev server after tailwind.config.js changes
- Clear browser cache

## Development Tips

1. Use Vue DevTools browser extension for debugging
2. Components hot-reload on save
3. Check router/index.js for all available routes
4. Use `console.log` or Vue DevTools to debug state
5. All forms validate on submit

## Next Steps

1. Connect to your Laravel backend
2. Customize theme in tailwind.config.js
3. Add your translations in src/locales/
4. Customize the dashboard in src/views/DashboardView.vue
5. Add your logo/branding
