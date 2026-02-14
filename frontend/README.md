# AutoERP - Frontend

A production-ready Vue.js 3 frontend application for the ERP SaaS platform, built with modern best practices and enterprise-grade architecture.

## 🚀 Tech Stack

- **Vue 3** - Progressive JavaScript Framework (Composition API)
- **Vite** - Next Generation Frontend Tooling
- **Vue Router** - Official routing library
- **Pinia** - State management
- **Vue I18n** - Internationalization
- **Axios** - HTTP client
- **Tailwind CSS** - Utility-first CSS framework
- **Heroicons** - Beautiful hand-crafted SVG icons

## 📁 Project Structure

```
src/
├── api/              # API client and endpoint modules
│   ├── client.js     # Axios instance with interceptors
│   ├── auth.js       # Authentication endpoints
│   ├── customers.js  # Customer endpoints
│   └── ...
├── assets/           # Static assets
├── components/       # Reusable components
│   ├── common/       # Shared components (Button, Input, Table, etc.)
│   └── layout/       # Layout components (Navbar, Sidebar, etc.)
├── composables/      # Vue composition functions
│   ├── useApi.js     # API call wrapper
│   └── useForm.js    # Form handling
├── layouts/          # Page layouts
│   └── DashboardLayout.vue
├── locales/          # i18n translations
├── modules/          # Feature modules
│   ├── auth/         # Authentication module
│   ├── crm/          # CRM module (Customers, Contacts, Vehicles)
│   ├── inventory/    # Inventory module (Products)
│   ├── billing/      # Billing module (Invoices)
│   └── appointments/ # Appointments module
├── router/           # Vue Router configuration
├── stores/           # Pinia stores
│   ├── auth.js       # Authentication store
│   ├── ui.js         # UI state store
│   └── tenant.js     # Tenant context store
├── utils/            # Utility functions
│   ├── formatters.js # Date, currency, number formatters
│   └── validators.js # Form validation helpers
├── views/            # Main app views
├── App.vue           # Root component
└── main.js           # Application entry point
```

## 🛠️ Setup & Installation

### Prerequisites
- Node.js 18+ and npm

### Install Dependencies
```bash
npm install
```

### Environment Configuration
Copy `.env.example` to `.env` and configure:
```env
VITE_API_BASE_URL=http://localhost:8000/api
VITE_APP_NAME=AutoERP
```

## 🚀 Development

Start the development server:
```bash
npm run dev
```

The application will be available at `http://localhost:5173`

## 🏗️ Build for Production

```bash
npm run build
```

Preview production build:
```bash
npm run preview
```

## 📦 Core Features

### Authentication
- Login/Register forms
- JWT token management
- Protected route guards
- Permission-based UI rendering

### State Management
- **Auth Store**: User authentication, permissions, roles
- **UI Store**: Sidebar, theme, loading states, notifications
- **Tenant Store**: Multi-tenant context management

### API Integration
- Axios client with request/response interceptors
- Automatic token injection
- Tenant context headers
- Centralized error handling

### Modules

#### CRM
- Customer management (CRUD)
- Contact management
- Vehicle tracking

#### Inventory
- Product catalog
- SKU management
- Stock tracking

#### Billing
- Invoice management
- PDF generation
- Payment tracking

#### Appointments
- Appointment scheduling
- Status management
- Calendar integration

## 🎨 UI Components

### Base Components
- `BaseButton` - Configurable button with variants
- `BaseInput` - Form input with validation
- `BaseSelect` - Dropdown select
- `BaseTable` - Data table with sorting
- `BaseCard` - Content card container
- `BaseModal` - Modal dialog

### Layout Components
- `DashboardLayout` - Main app layout
- `AppNavbar` - Top navigation bar
- `AppSidebar` - Side navigation menu
- `NotificationContainer` - Toast notifications

## 🌐 Routing

Routes are organized by feature modules with lazy loading:
- `/login` - Login page
- `/register` - Registration page
- `/` - Dashboard (protected)
- `/crm/customers` - Customer list
- `/inventory/products` - Product list
- `/billing/invoices` - Invoice list
- `/appointments` - Appointments

## 🔒 Authentication Flow

1. User logs in via `/login`
2. Token is stored in localStorage
3. Token is automatically included in API requests
4. Router guards check authentication
5. User is redirected to dashboard on success

## 🎯 Best Practices

- **Composition API**: All components use `<script setup>` syntax
- **Code Splitting**: Route-based lazy loading
- **Type Safety**: Prop validation and computed types
- **Error Handling**: Centralized error management
- **Responsive Design**: Mobile-first approach with Tailwind
- **Accessibility**: ARIA labels and keyboard navigation

## 🌍 Internationalization

Add translations in `src/locales/`:
```javascript
// en.js
export default {
  common: {
    save: 'Save',
    cancel: 'Cancel'
  }
}
```

Use in components:
```vue
<template>
  <button>{{ $t('common.save') }}</button>
</template>
```

## 📝 Adding a New Module

1. Create module directory: `src/modules/mymodule/`
2. Add API endpoints: `src/api/mymodule.js`
3. Create views: `src/modules/mymodule/views/`
4. Add routes: `src/router/index.js`
5. Add navigation: `src/components/layout/AppSidebar.vue`

## 🤝 Contributing

1. Follow the existing project structure
2. Use Composition API with `<script setup>`
3. Keep components small and focused
4. Write meaningful commit messages
5. Test all CRUD operations

## 📄 License

Proprietary - All rights reserved

