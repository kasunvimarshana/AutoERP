# API Integration Guide

## Base HTTP Client

All API calls go through `core/api/http.ts`, a singleton Axios instance.

```ts
import http from '@/core/api/http';
// or via composable:
import { useApi } from '@/composables/useApi';
const api = useApi();
```

### Automatic Headers

| Header | Value | When |
|--------|-------|------|
| `Authorization` | `Bearer <jwt>` | Always (when token exists) |
| `Content-Type` | `application/json` | Always |
| `Accept` | `application/json` | Always |
| `X-Correlation-ID` | UUID v4 | Every request |
| `Idempotency-Key` | UUID v4 | POST, PUT, PATCH, DELETE |

### Token Refresh

On a `401` response the client:

1. Pauses all in-flight requests
2. Calls `POST /api/v1/auth/refresh` with the current token
3. On success — replaces stored token, drains the queue with the new token, retries original request
4. On failure — clears token, redirects to `/login`

## Service Layer

Each domain has a service file in `resources/js/services/`:

| File | Endpoints covered |
|------|-------------------|
| `products.ts` | `GET/POST/PUT/DELETE /products` |
| `inventory.ts` | `/inventory/stock`, `/warehouses`, `/inventory/alerts/low-stock` |
| `orders.ts` | `/orders`, `PATCH /orders/{id}/confirm|cancel` |
| `invoices.ts` | `/invoices`, `PATCH /invoices/{id}/send|void` |
| `pos.ts` | `/pos/transactions`, `/reports/pos-sales-summary` |
| `purchases.ts` | `/purchases`, `/purchase-returns`, `/suppliers` |
| `crm.ts` | `/crm/contacts`, `/crm/leads`, `/crm/opportunities` |
| `accounting.ts` | `/accounting/accounts`, `/accounting/journal-entries`, `/accounting/periods` |
| `reports.ts` | All report endpoints |
| `users.ts` | `/users`, `/roles`, `/roles/permissions` |

## Calling Services

```ts
import { productService } from '@/services/products';

// List (paginated)
const { data } = await productService.list({ page: 1, per_page: 15, search: 'laptop' });

// Create
const { data: product } = await productService.create({
  name: 'Laptop',
  type: 'goods',
  base_price: '999.00',
  is_active: true,
});

// Update
await productService.update(product.id, { base_price: '1099.00' });

// Delete
await productService.remove(product.id);
```

## useListPage Composable

For list pages with pagination:

```ts
import { useListPage } from '@/composables/useListPage';
import type { Product } from '@/types/index';

const { items, loading, error, page, total, lastPage, load, nextPage, prevPage } =
  useListPage<Product>({
    endpoint: '/products',
    params: () => ({ search: search.value }),
  });

void load(); // initial load
```

## useFormSubmit Composable

For create/update forms:

```ts
import { useFormSubmit } from '@/composables/useFormSubmit';

const { saving, formError, submit } = useFormSubmit();

async function handleSubmit() {
  await submit({
    action: () => productService.create(payload),
    successMessage: 'Product created successfully.',
    onSuccess: () => {
      showForm.value = false;
      void load();
    },
  });
}
```

## Paginated Response Shape

```json
{
  "data": [...],
  "total": 100,
  "per_page": 15,
  "current_page": 1,
  "last_page": 7
}
```

## Error Response Shape

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "name": ["The name field is required."]
  }
}
```

Handle with:

```ts
try {
  await service.create(payload);
} catch (e) {
  const err = e as { response?: { data?: { message?: string } } };
  console.error(err.response?.data?.message ?? 'Unknown error');
}
```
