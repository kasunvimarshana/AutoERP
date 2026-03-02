# KV Enterprise ERP/CRM — Frontend

React (LTS) + TypeScript + Vite frontend for the KV Enterprise ERP/CRM platform.

---

## Technology Stack

| Layer | Technology |
|---|---|
| Framework | React 18 (LTS) |
| Language | TypeScript 5 (strict mode) |
| Build Tool | Vite 6 |
| Routing | React Router DOM v7 |
| State Management | Zustand v5 |
| Data Fetching | TanStack Query v5 |
| HTTP Client | Axios v1 |
| Testing | Vitest + Testing Library |

---

## Architecture

Feature-based component architecture following a strict module boundary pattern that mirrors the backend module structure. No business logic is duplicated from the backend — all domain decisions are enforced server-side.

```
frontend/src/
├── api/              # HTTP client and per-module API function files
│   ├── client.ts     # Axios singleton with JWT + tenant header interceptors
│   └── auth.ts       # Auth API (login, logout, refresh, me)
├── features/         # One directory per ERP/CRM module
│   ├── auth/         # Login page, AuthGuard, auth barrel
│   ├── dashboard/    # Landing page with module cards
│   ├── inventory/    # (planned)
│   ├── sales/        # (planned)
│   ├── pos/          # (planned)
│   ├── procurement/  # (planned)
│   ├── crm/          # (planned)
│   ├── warehouse/    # (planned)
│   ├── accounting/   # (planned)
│   ├── pricing/      # (planned)
│   ├── product/      # (planned)
│   ├── workflow/     # (planned)
│   ├── reporting/    # (planned)
│   ├── notification/ # (planned)
│   ├── organisation/ # (planned)
│   ├── tenancy/      # (planned)
│   ├── integration/  # (planned)
│   ├── plugin/       # (planned)
│   └── metadata/     # (planned)
├── components/
│   ├── layout/       # AppShell (header + sidebar + main)
│   ├── ui/           # Reusable primitive components
│   └── common/       # Shared composite components
├── hooks/            # Custom React hooks
├── store/            # Zustand stores (authStore, …)
├── types/            # Shared TypeScript types (api.ts, auth.ts, …)
├── utils/            # Pure utility functions
├── config/           # App-level config constants
└── test/             # Vitest setup and shared test utilities
```

### Module Boundary Rules

- Each `features/{module}/` directory owns its own pages, components, API calls, and local state.
- Cross-feature imports are only allowed through the feature's public `index.ts` barrel.
- Shared primitives go under `components/ui/` or `components/common/`.
- No business logic is implemented in the frontend — all validation, calculation, and domain rules live in the backend.

---

## Getting Started

```bash
# 1. Install dependencies
npm install

# 2. Copy and configure environment variables
cp .env.example .env
# Edit VITE_API_BASE_URL to point at your running Laravel backend

# 3. Start the development server
npm run dev

# 4. Run tests
npm test

# 5. Build for production
npm run build
```

---

## API Client

`src/api/client.ts` exports a pre-configured Axios instance that:

- Prefixes all requests with `/api/v1`
- Attaches `Authorization: Bearer <token>` from `localStorage.access_token`
- Forwards `X-Tenant-Slug` header from `localStorage.tenant_slug`
- Redirects to `/login` on HTTP 401

All API modules (`src/api/auth.ts`, etc.) import from this client and map exactly to the backend endpoint paths documented in the OpenAPI spec.

---

## State Management

Global state is managed with **Zustand**. Stores are located in `src/store/`.

| Store | Purpose |
|---|---|
| `authStore` | Authenticated user, JWT presence, loading flag |

Server state (paginated lists, entity detail) is managed with **TanStack Query** — each feature owns its query hooks.

---

## Authentication Flow

1. User submits credentials on `/login`.
2. `POST /api/v1/auth/login` returns a JWT.
3. Token is stored in `localStorage.access_token`; tenant slug in `localStorage.tenant_slug`.
4. `GET /api/v1/auth/me` hydrates the Zustand `authStore`.
5. `AuthGuard` wraps all protected routes and rehydrates the user on page refresh.
6. On 401, the Axios interceptor clears tokens and redirects to `/login`.

---

## Architecture Compliance

| Rule | Status |
|---|---|
| No business logic in frontend components | ✅ All domain rules enforced server-side |
| Strict API contract adherence | ✅ All API calls use versioned `/api/v1` endpoints |
| Feature-based component architecture | ✅ One directory per ERP/CRM module |
| Micro-frontend ready | ✅ Flat feature structure supports module federation |
| No cross-feature tight coupling | ✅ Cross-feature imports only through public barrels |
| TypeScript strict mode | ✅ Enabled in `tsconfig.json` |
| Test coverage | 🟡 In Progress — auth store and LoginPage tests added |
