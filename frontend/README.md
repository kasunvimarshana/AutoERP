# AutoERP Frontend

Modern Vue.js 3 frontend application for an enterprise-grade ERP SaaS platform.

## Technology Stack

- **Vue.js 3** - Progressive JavaScript framework with Composition API
- **Vite** - Next-generation frontend build tool
- **Vue Router** - Official routing library
- **Pinia** - State management
- **Vue i18n** - Internationalization (English, Spanish, French)
- **Tailwind CSS** - Utility-first CSS framework
- **Axios** - HTTP client

## Project Structure

```
frontend/
├── src/
│   ├── modules/          # Feature modules
│   │   ├── auth/         # Authentication
│   │   ├── dashboard/    # Dashboard
│   │   ├── tenant/       # Tenant management
│   │   ├── user/         # User management
│   │   ├── customer/     # Customer management
│   │   ├── vehicle/      # Vehicle management
│   │   ├── inventory/    # Inventory management
│   │   ├── product/      # Product catalog
│   │   ├── order/        # Order management
│   │   ├── invoice/      # Invoicing
│   │   ├── payment/      # Payment processing
│   │   └── reports/      # Reporting
│   ├── components/       # Shared components
│   │   ├── layouts/      # Layout components
│   │   └── common/       # Reusable UI components
│   ├── api/              # API client configuration
│   ├── composables/      # Composition functions
│   ├── stores/           # Global stores
│   ├── router/           # Route definitions
│   ├── locales/          # i18n translations
│   └── assets/           # Static assets
├── index.html
├── vite.config.js
├── tailwind.config.js
└── package.json
```

## Module Architecture

Each module follows a consistent structure:
- `views/` - Page components
- `components/` - Module-specific components
- `store/` - Pinia state management
- `api/` - API client methods

## Getting Started

### Installation

```bash
cd frontend
npm install
```

### Environment Configuration

Copy `.env.example` to `.env` and configure:

```bash
cp .env.example .env
```

Edit `.env`:
```
VITE_API_URL=http://localhost:5000
VITE_APP_TITLE=AutoERP
VITE_APP_VERSION=1.0.0
```

### Development Server

```bash
npm run dev
```

The application will be available at `http://localhost:3000`

### Build for Production

```bash
npm run build
```

### Preview Production Build

```bash
npm run preview
```

## Key Features

### Authentication
- Login/Register
- Password recovery
- JWT-based authentication
- Protected routes

### Multi-tenancy
- Tenant isolation
- Per-tenant configuration
- Tenant switching

### Internationalization
- English, Spanish, French
- Easy to add more languages
- Persistent language preference

### Responsive Design
- Mobile-first approach
- Tailwind CSS utilities
- Adaptive layouts

### Shared Components

- **BaseButton** - Customizable button with loading states
- **BaseInput** - Form input with validation
- **BaseCard** - Content container
- **BaseModal** - Dialog/modal component
- **BaseTable** - Data table with sorting

### State Management

Global state with Pinia:
- Authentication state
- App configuration
- Per-module stores

### API Integration

Centralized API client with:
- Automatic token injection
- Response/error interceptors
- Base URL configuration

## Development Guidelines

### Adding a New Module

1. Create module directory structure:
```bash
mkdir -p src/modules/mymodule/{views,components,store,api}
```

2. Create API client (`api/mymoduleApi.js`)
3. Create Pinia store (`store/mymoduleStore.js`)
4. Create views (`views/MymoduleListView.vue`, `views/MymoduleDetailView.vue`)
5. Add routes to `router/index.js`
6. Add translations to locale files

### Code Style

- Use Composition API with `<script setup>`
- Follow Vue 3 best practices
- Use Tailwind CSS utilities
- Keep components small and focused
- Extract reusable logic to composables

### Component Naming

- PascalCase for components
- Prefix shared components with "Base"
- Descriptive names (e.g., `UserListView`, `CustomerForm`)

## Available Scripts

- `npm run dev` - Start development server
- `npm run build` - Build for production
- `npm run preview` - Preview production build
- `npm run lint` - Lint and fix code

## Browser Support

- Modern browsers (Chrome, Firefox, Safari, Edge)
- ES2020+ support required

## License

Proprietary - All rights reserved
