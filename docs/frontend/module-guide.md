# Module Guide

## Architecture

Every business capability is implemented as an isolated module in `resources/js/modules/`. Modules communicate through:

- The **module registry** (`core/registry/moduleRegistry.ts`)
- Shared **types** (`types/index.ts`)
- The **API layer** (`core/api/http.ts` / `services/`)

## Module Directory Structure

```
resources/js/
├── core/
│   ├── api/          http.ts (Axios singleton + interceptors)
│   ├── auth/         (auth store at stores/auth.ts)
│   ├── layouts/
│   │   ├── AdminLayout.vue   (authenticated shell with grouped nav)
│   │   ├── AuthLayout.vue    (login/public pages shell)
│   │   └── MinimalLayout.vue (bare shell for embedded views)
│   ├── registry/
│   │   └── moduleRegistry.ts (register/list/feature-flag modules)
│   └── utils/
│       └── uuid.ts           (crypto.randomUUID helper)
│
├── modules/
│   ├── inventory/    index.ts — routes + nav for Products & Inventory
│   ├── pos/          index.ts — routes + nav for Orders, POS, Invoices
│   ├── purchases/    index.ts — routes + nav for Purchases
│   ├── crm/          index.ts — routes + nav for CRM
│   ├── accounting/   index.ts — routes + nav for Accounting
│   ├── reporting/    index.ts — routes + nav for Reports
│   └── identity/     index.ts — routes + nav for Users & Roles
│
├── shared/           (future shared utilities between modules)
│
├── pages/            Vue page components (one per route)
├── services/         API service functions (one per domain)
├── stores/           Pinia stores (auth, notifications)
├── composables/      Reusable composition functions
├── components/       Shared UI components
├── router/           Vue Router configuration
└── types/            TypeScript interfaces
```

## Registering a New Module

1. Create `resources/js/modules/{name}/index.ts`
2. Export a `ModuleDefinition` object
3. Import and call `registerModule()` in `router/index.ts`
4. Add a feature flag `VITE_MODULE_{NAME}` in `.env.example`

```ts
// modules/hr/index.ts
export const hrModule: ModuleDefinition = {
  id: 'hr',
  name: 'HR',
  featureFlag: 'hr',
  permissions: ['hr.view'],
  navItems: [
    { to: '/hr', label: 'HR', icon: '👔', permission: 'hr.view', group: 'Administration' },
  ],
  routes: [
    {
      path: 'hr',
      name: 'hr',
      component: () => import('@/pages/HrPage.vue'),
      meta: { permission: 'hr.view', module: 'hr' },
    },
  ],
};
```

## Module Pages

Pages live in `resources/js/pages/`. They use:

- `useListPage<T>()` — list + pagination + error handling
- `useFormSubmit<T>()` — form submit + saving state + error capture
- `usePermission()` — permission checks in templates
- `PermissionButton` — permission-aware action buttons
- `BaseDataTable` — reusable data table
- `DynamicForm` — metadata-driven forms
- `AppModal` — modal dialogs
- `AppToast` / `useNotificationStore` — success/error toasts

## Available Modules

| ID | Routes | Key Permissions |
|----|--------|-----------------|
| `inventory` | `/products`, `/inventory` | `product.*`, `inventory.view` |
| `pos` | `/orders`, `/pos`, `/invoices` | `order.*`, `pos.view`, `invoice.*` |
| `purchases` | `/purchases` | `purchase.*` |
| `crm` | `/crm` | `crm.*` |
| `accounting` | `/accounting` | `accounting.*` |
| `reporting` | `/reports` | `report.view` |
| `identity` | `/users` | `user.*` |

## Security

All API requests include:

- `Authorization: Bearer <jwt>` — stateless JWT authentication
- `X-Correlation-ID: <uuid>` — distributed tracing
- `Idempotency-Key: <uuid>` — duplicate-mutation prevention on POST/PUT/PATCH

Token refresh is handled transparently in `core/api/http.ts` with concurrent-request queuing.
