# Permission System

## Overview

The frontend enforces permissions at three layers:

1. **Route guard** — `router.beforeEach` blocks navigation if the user lacks the required permission
2. **UI components** — `PermissionButton` and conditional `v-if` using `usePermission()` hide/show elements
3. **Navigation menu** — `AdminLayout` filters nav items by permission

## How Permissions Are Resolved

```
JWT Login Response
  └── user.permissions: string[]       ← flat list from Spatie/permissions
  └── user.roles: [{ name: string }]   ← roles for UI display and super-admin bypass

useAuthStore.hasPermission(permission)
  ├── if no user loaded → false
  ├── if role === 'super-admin' → true (bypass all checks)
  └── else → user.permissions.includes(permission)
```

## Permission Naming Convention

```
{resource}.{action}

Examples:
  product.view      product.create     product.update     product.delete
  inventory.view
  order.view        order.create       order.update
  invoice.view      invoice.create
  pos.view
  purchase.view     purchase.create    purchase.update    purchase.delete
  crm.view          crm.create         crm.update         crm.delete
  accounting.view   accounting.create  accounting.update
  report.view
  user.view         user.create        user.update        user.delete
  workflow.view     workflow.manage
```

## Using the `usePermission` Composable

```ts
import { usePermission } from '@/composables/usePermission';

const { can, canAny, canAll, hasRole, isSuperAdmin } = usePermission();

// Check single permission
if (can('product.create')) { ... }

// Check any permission
if (canAny(['order.create', 'pos.view'])) { ... }

// Check all permissions
if (canAll(['accounting.view', 'accounting.create'])) { ... }

// Check role
if (hasRole('manager')) { ... }
```

## Using `PermissionButton`

```vue
<PermissionButton permission="product.create" @click="openCreate">
  New Product
</PermissionButton>

<PermissionButton permission="product.delete" variant="danger" size="sm" @click="confirmDelete(row)">
  Delete
</PermissionButton>
```

The button renders nothing if the user lacks the permission.

## Route Guards

Routes use `meta.permission`:

```ts
{
  path: 'products',
  name: 'products',
  component: () => import('@/pages/ProductsPage.vue'),
  meta: { permission: 'product.view', module: 'inventory' }
}
```

The guard in `router/index.ts` redirects to `/dashboard` if the user lacks the permission.

## Module Feature Flags

Each module can be disabled independently via `.env`:

```dotenv
VITE_MODULE_POS=false   # disables POS routes and nav items
```

The `moduleRegistry.ts` reads these flags at build/startup time.
