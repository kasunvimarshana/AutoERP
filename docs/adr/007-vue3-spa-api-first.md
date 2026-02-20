# ADR-007: Vue 3 SPA + API-First Frontend Architecture

**Status:** Accepted  
**Date:** 2026-02-20  
**Author:** Engineering Team

---

## Context

The ERP/CRM platform already provides a complete, production-ready JSON API layer
(`/api/v1/*`) built on Laravel 11.  The frontend layer was identified as the
remaining major unimplemented gap (listed as ⬜ in `MODULE_STATUS.md`).

We needed to decide:
1. The SPA framework (Vue 3 vs. React vs. Livewire vs. Inertia.js)
2. State management strategy
3. Client-side routing approach
4. Auth token storage and refresh strategy

---

## Decision

We adopt **Vue 3 (Composition API + `<script setup>`)** as the SPA framework,
**Pinia** as the state management library, and **Vue Router 4** for client-side
routing.  All data fetching is performed through the existing `/api/v1` REST API
using a shared **Axios** instance (`resources/js/composables/useApi.js`).

---

## Rationale

### Vue 3 over alternatives

| Option | Reason rejected / accepted |
|--------|---------------------------|
| **Vue 3** ✅ | Officially listed in project requirements; Composition API enables clean, testable composables; excellent TypeScript support path |
| React | Not mentioned in requirements; heavier ecosystem; team familiarity with Vue |
| Livewire | Tight Blade coupling; harder to separate backend from frontend; HTTPS + JWT stateless auth model fits SPA better |
| Inertia.js | Still server-rendered; complicates pure API-first design; JWT auth is harder to maintain |

### Pinia over Vuex

Pinia is the officially recommended state management library for Vue 3.  It
provides a simpler, composable API with full TypeScript inference and no
mutations boilerplate.  The `useAuthStore` encapsulates JWT token lifecycle
including `localStorage` persistence, automatic refresh, and `me()` hydration.

### Client-side routing (Vue Router 4)

Vue Router 4 supports history mode, lazy-loaded routes (code splitting via
dynamic `import()`), and navigation guards for auth protection.  The Laravel
web route catch-all (`/{any?}`) forwards all non-API requests to `app.blade.php`
which mounts the Vue app and lets Vue Router take over.

### JWT storage: localStorage vs. httpOnly cookie

We store the JWT in `localStorage` because:
- The backend is purely stateless (no session middleware on API routes)
- `httpOnly` cookies require CSRF tokens and cookie domain configuration that
  complicates multi-origin SaaS deployments
- The threat model (XSS) is mitigated by CSP headers (planned) and sanitised
  inputs in all form requests

In a future iteration, a **Refresh Token rotation** pattern with httpOnly
refresh tokens will be considered (see Security Considerations below).

---

## Architecture

```
resources/
└── js/
    ├── app.js                       # Vue app bootstrap (mount #app)
    ├── App.vue                      # Root component — <RouterView>
    ├── router/
    │   └── index.js                 # Route definitions + auth guard
    ├── stores/
    │   └── auth.js                  # Pinia auth store (JWT, user)
    ├── composables/
    │   └── useApi.js                # Axios instance with JWT interceptors
    ├── pages/
    │   ├── LoginPage.vue
    │   ├── DashboardPage.vue
    │   ├── ProductsPage.vue
    │   ├── OrdersPage.vue
    │   ├── InvoicesPage.vue
    │   ├── UsersPage.vue
    │   └── InventoryPage.vue
    └── components/
        ├── AppLayout.vue            # Sidebar + header shell
        └── StatCard.vue             # Reusable KPI card
```

### Route protection

The `router/index.js` `beforeEach` guard checks `auth.isAuthenticated`.
Unauthenticated users are redirected to `/login` with a `?redirect=` parameter
so they return to the intended page after signing in.

### API client

`useApi.js` returns a configured Axios instance that:
1. Attaches `Authorization: Bearer <token>` from `localStorage` on every request
2. On a `401` response, clears the stored token and redirects to `/login`

---

## Consequences

### Positive
- Complete decoupling of frontend from backend — any future mobile app or
  third-party integration can consume the same API
- Fast initial loads via lazy-loaded route chunks (Vite code splitting)
- Tailwind CSS utility classes enable rapid, consistent UI development
- Composition API composables are easily unit-tested with Vitest

### Negative / Trade-offs
- Requires JavaScript to be enabled (not a concern for enterprise ERP/CRM)
- SEO is irrelevant for an authenticated ERP, but SSR is not possible without
  additional tooling (Nuxt 3) — acceptable trade-off for this use case
- `localStorage` JWT storage is susceptible to XSS; mitigated by strict CSP

---

## Security Considerations

- `ForceHttps` middleware (`app/Http/Middleware/ForceHttps.php`) ensures all
  traffic is encrypted in transit when `FORCE_HTTPS=true`
- HSTS header (`Strict-Transport-Security`) is added for all HTTPS responses
- Future: add `Content-Security-Policy` header to prevent XSS
- Future: consider Refresh Token rotation with httpOnly cookies for higher
  security environments

---

## Related Decisions

- ADR-003: JWT Stateless Authentication
- ADR-006: HTTP Idempotency Keys
