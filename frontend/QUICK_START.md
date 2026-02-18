# Quick Start Guide - AutoERP Frontend

## Installation (5 minutes)

```bash
cd frontend
npm install
cp .env.example .env
npm run dev
```

Access at: **http://localhost:5173**

## Demo Credentials

- **Email**: admin@demo.com
- **Password**: password
- **Tenant**: demo-company

## Project Structure

```
src/
├── components/      # Reusable components
├── modules/         # 8 feature modules with views
├── services/        # API integration layer
├── stores/          # Pinia state management
├── router/          # Vue Router config
└── layouts/         # Page layouts
```

## Key Files

| File | Purpose |
|------|---------|
| `src/router/index.js` | All routes (28+) |
| `src/services/api.js` | API client configuration |
| `src/stores/authStore.js` | Authentication state |
| `src/App.vue` | Root component |
| `src/main.js` | Application entry point |

## Available Routes

- `/login` - Login page
- `/dashboard` - Main dashboard
- `/iam/users` - User management
- `/inventory/products` - Products
- `/crm/customers` - Customers
- `/pos/sales-orders` - Sales orders
- And 22+ more routes...

## Common Tasks

### Add a New View
1. Create view in `src/modules/[module]/views/`
2. Add route in `src/router/index.js`
3. Add navigation item in `Sidebar.vue`

### Add API Endpoint
1. Add method in `src/services/[module]Service.js`
2. Call from store action
3. Update UI component

### Create Reusable Component
1. Create in `src/components/common/` or `src/components/forms/`
2. Accept props, emit events
3. Add consistent styling

## Build for Production

```bash
npm run build
# Output in dist/
```

## Environment Variables

```env
VITE_API_BASE_URL=http://localhost:8000/api/v1
VITE_APP_NAME=AutoERP
```

## Tech Stack

- Vue 3.5 + Composition API
- Vite 7.2
- Pinia 3.0
- Vue Router 4.6
- Axios 1.13
- Heroicons
- Headless UI

## Need Help?

- Full docs: `frontend/README.md`
- Implementation details: `frontend/FRONTEND_IMPLEMENTATION.md`
- Complete summary: `FRONTEND_COMPLETE.md`

## Status

✅ **PRODUCTION READY** - All features implemented and tested!
