# AutoERP Frontend - Enterprise Vue.js Application

## 🎯 Overview

Production-ready, enterprise-grade frontend for AutoERP - a comprehensive SaaS platform for vehicle service centers and auto repair garages. Built with **Vue.js 3**, **TypeScript**, **Pinia**, and **Tailwind CSS** following **Clean Architecture** principles.

## ✨ Key Features

### Architecture & Design
- **Clean Architecture**: Strict separation of concerns (Presentation, State, Services, Domain)
- **Type-Safe**: Full TypeScript coverage with comprehensive type definitions
- **Modular Structure**: Feature-based organization with reusable components
- **SOLID Principles**: Maintainable, testable, and scalable code

### User Experience
- **Responsive Design**: Mobile-first approach, works on desktop, tablet, and mobile
- **Dark Mode**: Built-in theme switching
- **Accessibility**: ARIA labels, keyboard navigation support
- **Professional UI**: Clean, modern interface with Tailwind CSS
- **Toast Notifications**: Non-intrusive user feedback system
- **Loading States**: Visual feedback for async operations

### Security & Authentication
- **Token-Based Auth**: JWT authentication with automatic token management
- **Role-Based Access Control**: Permission-based UI composition
- **Route Guards**: Protected routes with auth and permission checks
- **Tenant Awareness**: Multi-tenant context management
- **Secure API Client**: Automatic token injection and error handling

### State Management
- **Pinia Stores**: Reactive state management
- **Auth Store**: User authentication and authorization state
- **UI Store**: Notifications, theme, sidebar state
- **Composables**: Reusable logic (useAuth, useNotification, useApi, usePagination)

### API Integration
- **Axios-Based**: Enhanced HTTP client with interceptors
- **Error Handling**: Comprehensive error management
- **Request/Response Interceptors**: Automatic token and tenant ID injection
- **Type-Safe Services**: Strongly typed API service layer

## 🏗️ Project Structure

```
frontend/
├── public/                    # Static assets
├── src/
│   ├── assets/               # Images, fonts, styles
│   ├── components/           # Vue components
│   │   ├── layout/          # Layout components (Header, Sidebar, Footer)
│   │   ├── ui/              # Reusable UI components (Button, Input, Modal)
│   │   └── forms/           # Form components
│   ├── composables/          # Composition API utilities
│   │   ├── useAuth.ts       # Authentication composable
│   │   ├── useNotification.ts  # Notification composable
│   │   ├── useApi.ts        # API call wrapper
│   │   └── usePagination.ts # Pagination helper
│   ├── config/               # Configuration files
│   │   └── app.ts           # App configuration
│   ├── layouts/              # Page layouts
│   │   ├── AppLayout.vue    # Main application layout
│   │   ├── AuthLayout.vue   # Authentication pages layout
│   │   └── ErrorLayout.vue  # Error pages layout
│   ├── locales/              # i18n translation files
│   ├── middleware/           # Route middleware
│   │   └── auth.ts          # Authentication guards
│   ├── router/               # Vue Router configuration
│   │   └── index.ts         # Route definitions
│   ├── services/             # API service layer
│   │   ├── api.ts           # Enhanced Axios instance
│   │   ├── authService.ts   # Authentication API
│   │   ├── customerService.ts  # Customer API
│   │   └── ...              # Other service modules
│   ├── stores/               # Pinia state stores
│   │   ├── auth.ts          # Auth state
│   │   ├── ui.ts            # UI state
│   │   ├── customer.ts      # Customer state
│   │   └── ...              # Other stores
│   ├── types/                # TypeScript type definitions
│   │   ├── auth.ts          # Auth types
│   │   ├── api.ts           # API response types
│   │   ├── customer.ts      # Customer types
│   │   └── ...              # Other type definitions
│   ├── utils/                # Utility functions
│   ├── views/                # Page components
│   │   ├── auth/            # Authentication pages
│   │   ├── customers/       # Customer management
│   │   ├── vehicles/        # Vehicle management
│   │   ├── appointments/    # Appointment scheduling
│   │   ├── jobcards/        # Job card management
│   │   ├── inventory/       # Inventory management
│   │   ├── invoices/        # Invoicing
│   │   ├── settings/        # Settings
│   │   └── errors/          # Error pages
│   ├── App.vue              # Root component
│   └── main.ts              # Application entry point
├── .env                      # Environment variables
├── index.html               # HTML template
├── package.json             # Dependencies
├── tsconfig.json            # TypeScript configuration
├── vite.config.ts           # Vite configuration
└── tailwind.config.js       # Tailwind CSS configuration
```

## 🚀 Getting Started

### Prerequisites

- Node.js 20.x or higher
- npm 9.x or higher

### Installation

```bash
# Navigate to frontend directory
cd frontend

# Install dependencies
npm install

# Create environment file
cp .env.example .env

# Update API URL in .env
echo "VITE_API_URL=http://localhost:8000/api/v1" > .env
```

### Development

```bash
# Start development server
npm run dev

# Application will be available at http://localhost:5173
```

### Build for Production

```bash
# Type check and build
npm run build

# Preview production build
npm run preview
```

## 🎨 UI Components

### Base Components

#### BaseButton
Reusable button component with multiple variants and states.

```vue
<BaseButton
  variant="primary"
  size="md"
  :loading="isLoading"
  @click="handleClick"
>
  Click Me
</BaseButton>
```

**Props:**
- `variant`: 'primary' | 'secondary' | 'success' | 'danger' | 'warning' | 'info'
- `size`: 'sm' | 'md' | 'lg'
- `loading`: boolean
- `disabled`: boolean
- `fullWidth`: boolean
- `outline`: boolean

#### BaseInput
Form input component with validation and error states.

```vue
<BaseInput
  v-model="email"
  type="email"
  label="Email Address"
  :error="errors.email"
  required
/>
```

**Props:**
- `modelValue`: string | number
- `type`: 'text' | 'email' | 'password' | 'number' | 'tel' | 'url'
- `label`: string
- `error`: string
- `hint`: string
- `required`: boolean
- `disabled`: boolean

#### BaseModal
Modal dialog component.

```vue
<BaseModal
  :show="isOpen"
  title="Confirm Action"
  @close="isOpen = false"
>
  <p>Are you sure?</p>
  
  <template #footer>
    <BaseButton @click="handleConfirm">Confirm</BaseButton>
  </template>
</BaseModal>
```

## 🔐 Authentication

### Login

```typescript
import { useAuth } from '@/composables/useAuth'

const { login } = useAuth()

await login({
  email: 'user@example.com',
  password: 'password123',
  remember: true
})
```

### Register

```typescript
await register({
  name: 'John Doe',
  email: 'john@example.com',
  password: 'password123',
  password_confirmation: 'password123',
  tenant_name: 'My Business'
})
```

### Logout

```typescript
const { logout } = useAuth()
await logout()
```

### Check Permissions

```typescript
const { hasPermission, hasRole } = useAuth()

if (hasPermission('customers.create')) {
  // Show create button
}

if (hasRole('admin')) {
  // Show admin features
}
```

## 🛡️ Route Guards

### Authentication Guard

```typescript
{
  path: '/dashboard',
  component: DashboardView,
  beforeEnter: authGuard,
  meta: { requiresAuth: true }
}
```

### Role-Based Guard

```typescript
{
  path: '/settings',
  component: SettingsView,
  beforeEnter: roleGuard(['admin', 'super_admin']),
  meta: { requiresRole: ['admin', 'super_admin'] }
}
```

### Permission-Based Guard

```typescript
{
  path: '/customers/create',
  component: CustomerCreateView,
  beforeEnter: permissionGuard(['customers.create']),
  meta: { requiresPermission: ['customers.create'] }
}
```

## 📦 State Management

### Using Stores

```typescript
import { useAuthStore } from '@/stores/auth'
import { useUiStore } from '@/stores/ui'

// Auth store
const authStore = useAuthStore()
authStore.login(credentials)
authStore.logout()

// UI store
const uiStore = useUiStore()
uiStore.success('Operation successful!')
uiStore.toggleTheme()
```

### Using Composables

```typescript
import { useAuth } from '@/composables/useAuth'
import { useNotification } from '@/composables/useNotification'

const { user, isAuthenticated } = useAuth()
const { success, error } = useNotification()

success('Customer created successfully!')
```

## 🔄 API Integration

### Making API Calls

```typescript
import { customerService } from '@/services/customerService'

// Get customers
const customers = await customerService.getCustomers({ 
  page: 1, 
  per_page: 15 
})

// Create customer
const newCustomer = await customerService.createCustomer({
  first_name: 'John',
  last_name: 'Doe',
  email: 'john@example.com'
})
```

### Using API Composable

```typescript
import { useApi } from '@/composables/useApi'
import { customerService } from '@/services/customerService'

const { data, isLoading, error, execute } = useApi(
  customerService.getCustomers,
  {
    immediate: true,
    showSuccessMessage: false
  }
)

// Execute manually
await execute({ page: 1 })
```

## 🎨 Theming

### Toggle Theme

```typescript
import { useUiStore } from '@/stores/ui'

const uiStore = useUiStore()
uiStore.toggleTheme()
```

### Set Theme

```typescript
uiStore.setTheme('dark')  // or 'light'
```

## 📱 Responsive Design

The application is fully responsive with breakpoints:

- **Mobile**: < 640px
- **Tablet**: 640px - 1024px
- **Desktop**: > 1024px

All components are designed mobile-first and scale up.

## 🧪 Testing

```bash
# Run unit tests (when implemented)
npm run test:unit

# Run E2E tests (when implemented)
npm run test:e2e
```

## 📝 Code Style

The project follows Vue.js and TypeScript best practices:

- Use Composition API with `<script setup>`
- TypeScript for all logic
- Tailwind CSS for styling
- ESLint for code quality
- Prettier for formatting

## 🚀 Deployment

### Build

```bash
npm run build
```

The built files will be in the `dist/` directory.

### Environment Variables

Create a `.env` file:

```env
VITE_API_URL=https://api.yourdomain.com/api/v1
VITE_APP_NAME=AutoERP
VITE_APP_VERSION=1.0.0
```

### Nginx Configuration

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /var/www/autoerp/frontend/dist;
    index index.html;

    location / {
        try_files $uri $uri/ /index.html;
    }

    location /api {
        proxy_pass http://backend:8000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }
}
```

## 🤝 Contributing

1. Follow the existing code structure
2. Use TypeScript for type safety
3. Write reusable components
4. Add proper documentation
5. Test your changes

## 📄 License

Proprietary. All rights reserved.

---

**Built with ❤️ for the automotive service industry**
