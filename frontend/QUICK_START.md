# Quick Start Guide

## 🚀 Getting Started (5 Minutes)

### 1. Setup Environment
```bash
cd frontend
cp .env.example .env
```

Edit `.env`:
```env
VITE_API_URL=http://localhost:5000
VITE_APP_TITLE=AutoERP
```

### 2. Start Development
```bash
npm run dev
```
Opens at: http://localhost:3000

### 3. Default Routes
- `/auth/login` - Login page
- `/auth/register` - Registration
- `/` - Dashboard (requires auth)
- `/tenants` - Tenant management
- `/users` - User management
- `/customers` - Customer management
- `/products` - Products
- `/orders` - Orders
- `/invoices` - Invoices
- `/payments` - Payments
- `/reports` - Reports

## 📦 Key Commands

```bash
# Development
npm run dev          # Start dev server with HMR

# Production
npm run build        # Build for production
npm run preview      # Preview production build

# Code Quality
npm run lint         # Lint and fix code
```

## 🎨 Using Components

### BaseButton
```vue
<BaseButton variant="primary" @click="handleClick">
  Click Me
</BaseButton>

<!-- Variants: primary, secondary, danger, success -->
<!-- Sizes: sm, md, lg -->
<!-- With loading: :loading="true" -->
```

### BaseInput
```vue
<BaseInput
  v-model="email"
  type="email"
  label="Email Address"
  :error="errors.email"
  required
/>
```

### BaseCard
```vue
<BaseCard title="Card Title">
  <p>Card content goes here</p>
  
  <template #footer>
    <BaseButton>Action</BaseButton>
  </template>
</BaseCard>
```

### BaseModal
```vue
<BaseModal v-model="showModal" title="Modal Title">
  <p>Modal content</p>
  
  <template #footer>
    <BaseButton @click="showModal = false">Close</BaseButton>
  </template>
</BaseModal>
```

### BaseTable
```vue
<BaseTable
  :columns="columns"
  :data="items"
  :actions="true"
>
  <template #actions="{ row }">
    <button @click="edit(row)">Edit</button>
  </template>
</BaseTable>
```

## 🔐 Authentication

### Login
```javascript
import { useAuthStore } from '@/modules/auth/store/authStore'

const authStore = useAuthStore()

await authStore.login({
  email: 'user@example.com',
  password: 'password'
})
```

### Check Auth
```javascript
if (authStore.isAuthenticated) {
  // User is logged in
  console.log(authStore.user)
}
```

### Logout
```javascript
authStore.logout()
```

## 🌐 API Calls

### Using Store
```javascript
import { useProductStore } from '@/modules/product/store/productStore'

const productStore = useProductStore()

// Fetch all
await productStore.fetchAll()

// Fetch by ID
await productStore.fetchById(123)

// Create
await productStore.create(data)

// Update
await productStore.update(123, data)

// Delete
await productStore.remove(123)
```

### Direct API Call
```javascript
import apiClient from '@/api/client'

const response = await apiClient.get('/products')
const products = response.data
```

## 🌍 Internationalization

### Change Language
```javascript
import { useI18n } from 'vue-i18n'

const { locale } = useI18n()
locale.value = 'es' // or 'en', 'fr'
```

### Use Translations
```vue
<template>
  <h1>{{ $t('dashboard.title') }}</h1>
  <button>{{ $t('common.save') }}</button>
</template>
```

## 📊 State Management

### Create Module Store
```javascript
import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useMyStore = defineStore('myStore', () => {
  const items = ref([])
  
  const fetchItems = async () => {
    // API call
  }
  
  return { items, fetchItems }
})
```

### Use Store
```vue
<script setup>
import { useMyStore } from '@/stores/myStore'

const myStore = useMyStore()
</script>

<template>
  <div v-for="item in myStore.items">
    {{ item.name }}
  </div>
</template>
```

## 🎯 Adding New Module

1. Create structure:
```bash
mkdir -p src/modules/mymodule/{api,store,views,components}
```

2. Create API client (`api/mymoduleApi.js`)
3. Create store (`store/mymoduleStore.js`)
4. Create views (`views/MymoduleListView.vue`)
5. Add routes to `router/index.js`
6. Add translations to `locales/*.json`

## 🔧 Environment Variables

Available in code as:
```javascript
const apiUrl = import.meta.env.VITE_API_URL
const appTitle = import.meta.env.VITE_APP_TITLE
```

## 📱 Responsive Design

All components use Tailwind's responsive utilities:
```vue
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4">
  <!-- Responsive grid -->
</div>
```

## 🐛 Common Issues

### Port already in use
```bash
# Change port in vite.config.js
server: { port: 3001 }
```

### API not connecting
1. Check `.env` file has correct `VITE_API_URL`
2. Ensure backend is running
3. Check CORS configuration on backend

### Build errors
```bash
# Clear cache and rebuild
rm -rf node_modules dist
npm install
npm run build
```

## 📚 Resources

- Vue.js: https://vuejs.org/
- Vite: https://vitejs.dev/
- Tailwind CSS: https://tailwindcss.com/
- Vue Router: https://router.vuejs.org/
- Pinia: https://pinia.vuejs.org/
- Vue i18n: https://vue-i18n.intlify.dev/

## ✅ Checklist

- [ ] Environment configured
- [ ] Dependencies installed
- [ ] Dev server running
- [ ] Backend API connected
- [ ] Login working
- [ ] Navigation working
- [ ] API calls successful

Happy coding! 🚀
