# AutoERP - Frontend

A comprehensive, production-ready Vue 3 frontend for the AutoERP platform.

## Features

- ✅ **Complete Module Coverage**: IAM, Inventory, CRM, POS, Procurement, Manufacturing, Finance, Reporting
- ✅ **Professional Dashboard Layout**: Sidebar navigation, header with user menu, breadcrumbs
- ✅ **Responsive Design**: Mobile, tablet, and desktop support
- ✅ **State Management**: Pinia stores for each module
- ✅ **API Integration**: Comprehensive service layer for all 234+ backend endpoints
- ✅ **Reusable Components**: DataTable, Modal, Form inputs (Input, Select, Textarea)
- ✅ **Notifications**: Toast notifications and notification panel
- ✅ **Authentication**: Login/logout with JWT token management
- ✅ **Professional UI**: Modern, clean design with consistent styling

## Tech Stack

- **Vue 3** - Progressive JavaScript framework
- **Vite** - Next-generation frontend build tool
- **Pinia** - State management
- **Vue Router** - Official routing library
- **Axios** - HTTP client for API calls
- **Heroicons** - Beautiful SVG icons
- **Headless UI** - Unstyled, accessible UI components

## Project Structure

```
src/
├── components/          # Reusable components
│   ├── common/         # Common components (DataTable, Modal, Notifications)
│   ├── forms/          # Form components (Input, Select, Textarea)
│   └── layout/         # Layout components (Sidebar, Header, Breadcrumb)
├── layouts/            # Page layouts
│   └── DashboardLayout.vue
├── modules/            # Feature modules
│   ├── iam/           # Identity & Access Management
│   ├── inventory/     # Inventory Management
│   ├── crm/           # Customer Relationship Management
│   ├── pos/           # Point of Sale
│   ├── procurement/   # Procurement
│   ├── manufacturing/ # Manufacturing
│   ├── finance/       # Finance
│   └── reporting/     # Reporting & Analytics
├── router/            # Vue Router configuration
├── services/          # API service layer
├── stores/            # Pinia state stores
├── views/             # Page views
│   ├── auth/         # Authentication views
│   └── Dashboard.vue
├── App.vue           # Root component
├── main.js           # Application entry point
└── style.css         # Global styles
```

## Getting Started

### Prerequisites

- Node.js 18+ and npm
- Backend API running on http://localhost:8000

### Installation

```bash
# Install dependencies
npm install

# Set up environment variables
cp .env.example .env
# Edit .env to configure API URL if needed

# Start development server
npm run dev
```

### Development

```bash
# Run dev server (with hot reload)
npm run dev

# Build for production
npm run build

# Preview production build
npm run preview
```

## Available Routes

- `/login` - Login page
- `/dashboard` - Main dashboard
- `/iam/users` - User management
- `/iam/roles` - Role management
- `/iam/permissions` - Permission management
- `/inventory/products` - Product management
- `/inventory/stock-ledgers` - Stock ledger view
- `/inventory/stock-movements` - Stock movements
- `/inventory/warehouses` - Warehouse management
- `/crm/customers` - Customer management
- `/crm/contacts` - Contact management
- `/pos/quotations` - Quotations
- `/pos/sales-orders` - Sales orders
- `/pos/invoices` - Invoices
- `/pos/payments` - Payments
- `/procurement/suppliers` - Supplier management
- `/procurement/purchase-orders` - Purchase orders
- `/procurement/grns` - Goods Receipt Notes
- `/manufacturing/boms` - Bill of Materials
- `/manufacturing/production-orders` - Production orders
- `/manufacturing/work-orders` - Work orders
- `/finance/accounts` - Account management
- `/finance/journal-entries` - Journal entries
- `/finance/reports` - Financial reports
- `/reporting/dashboards` - Report dashboards
- `/reporting/analytics` - Analytics

## State Management

Each module has its own Pinia store:

- `authStore` - Authentication state
- `iamStore` - IAM module state
- `inventoryStore` - Inventory module state
- `crmStore` - CRM module state
- `notificationStore` - Notifications
- `uiStore` - UI state (loading, modals)

## API Services

Centralized API services for each module:

- `authService` - Authentication endpoints
- `iamService` - IAM endpoints
- `inventoryService` - Inventory endpoints
- `crmService` - CRM endpoints
- `posService` - POS endpoints
- `procurementService` - Procurement endpoints
- `manufacturingService` - Manufacturing endpoints
- `financeService` - Finance endpoints
- `reportingService` - Reporting endpoints

## Components

### Common Components

- **DataTable** - Sortable, paginated data table with actions
- **Modal** - Reusable modal dialog (small, medium, large sizes)
- **NotificationContainer** - Toast notification display
- **NotificationPanel** - Slide-out notification panel

### Form Components

- **FormInput** - Text/number/email input with validation
- **FormSelect** - Dropdown select with validation
- **FormTextarea** - Multi-line text input

### Layout Components

- **Sidebar** - Navigation sidebar with collapsible submenus
- **Header** - Top header with user menu and notifications
- **Breadcrumb** - Automatic breadcrumb navigation

## Authentication

The app uses JWT token-based authentication:

1. Login at `/login`
2. Token stored in localStorage
3. Token attached to API requests via Axios interceptor
4. Automatic redirect to login on 401 responses

## Styling

- Modern, professional design
- Consistent color scheme (primary blue: #3b82f6)
- Responsive breakpoints
- Utility classes for common patterns
- Custom scrollbar styling

## Best Practices

- **Composition API** - Using Vue 3 Composition API throughout
- **Reactive State** - Reactive refs and computed properties
- **TypeScript Ready** - Structure supports easy TypeScript migration
- **Component Reusability** - DRY principle with reusable components
- **Separation of Concerns** - Services, stores, and components separated
- **Error Handling** - Consistent error handling with notifications

## Customization

### Changing Colors

Edit the CSS variables in `src/style.css`:

```css
:root {
  --primary-color: #3b82f6;
  --primary-dark: #2563eb;
  /* ... other colors */
}
```

### Adding New Modules

1. Create module directory in `src/modules/`
2. Add views, components, and services
3. Create Pinia store in `src/stores/`
4. Add routes in `src/router/index.js`
5. Add navigation item in `Sidebar.vue`

## Production Build

```bash
# Build for production
npm run build

# Output will be in the dist/ directory
# Deploy to your preferred hosting service
```

## Browser Support

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)

## License

Proprietary - AutoERP Platform

## Support

For issues and support, contact the development team.
