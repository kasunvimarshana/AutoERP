# Permanent Remediation of Recurring 401 Unauthorized Errors and Concurrent Token Refresh Rotation

**Date:** 2026-11-22  
**Scope:** Authentication & Authorization Subsystems, Token Handling & Refresh, Concurrency Coordination (Backend: `Modules\Auth`, Frontend: `resources/js/shared/api`)  
**Standard:** Adherence to the 12 Core Engineering Axioms in `AGENTS.md` and RFC 6819 / OAuth 2.0 Security BCP Refresh Token Rotation specifications  

---

## 1. Problem Context & Root Cause Analysis

In high-concurrency scenarios, multi-tab usage, or simultaneous API dispatches when an access token expires:
1. **Backend Zero-Tolerance Refresh Token Rotation Race Condition**:
   - In `TenantTokenService.php` and `PlatformTokenService.php`, presenting a refresh token whose status was `ROTATED` immediately triggered `compromiseTenantFamily` / `compromisePlatformFamily` with reason `refresh_token_reuse_detected`.
   - When multiple concurrent requests (or two browser tabs) dispatched a refresh request with the existing refresh token cookie nearly simultaneously (e.g. within milliseconds of each other), Request 1 succeeded and rotated the token, but Request 2 locked the row, observed `status = ROTATED`, and triggered token family compromise.
   - This revoked all active refresh tokens in the family and revoked the user's active session, kicking the user out of the application with recurring `401 Unauthorized` (`AUTH_TOKEN_REVOKED`).
   - Furthermore, inside `TenantTokenService` and `PlatformTokenService`, `compromiseTenantFamily` was originally invoked within the outer database transaction that threw `AuthFailure`, meaning transaction rollbacks prevented the explicit revocation from persisting in audit state.

2. **Frontend Web Locks Omission & Cross-Tab Desynchronization**:
   - In `resources/js/shared/api/authRefreshCoordinator.ts`, while `navigator.locks` deduplicated requests inside the browser lock queue, upon completion of a refresh in Tab 1, the new access token was kept strictly in Tab 1's memory (`accessTokenInMemory`).
   - Tab 1 never broadcast the refreshed token across tabs, meaning when Tab 2 acquired the lock next, it had no knowledge that Tab 1 had just completed a refresh and fired a redundant `/refresh` HTTP call with the old cookie.

3. **Frontend Interceptor Dispatch Race Condition**:
   - In `resources/js/shared/api/apiClient.ts`, when a token refresh was currently in progress (`isRefreshInProgress()`), newly initiated non-public API requests were dispatched immediately using the stale/expired token, creating unnecessary 401 failures.
   - In the response interceptor, when a 401 returned, if another in-flight request had already completed a refresh (so `context.accessToken` in memory had already changed), the interceptor triggered another unnecessary refresh instead of retrying immediately with the fresh token.

---

## 2. Issues Permanently Remediated

### 2.1 Backend OAuth 2.0 / RFC 6819 Refresh Token Rotation Grace Window
- **Configuration**:
  - Added `'rotation_grace_seconds' => (int) env('AUTH_REFRESH_ROTATION_GRACE_SECONDS', 30)` to `app/Modules/Auth/Config/auth.php`.
  - Added `public int $rotationGraceSeconds = 30` to `AuthSecurityConfig.php` with range assertion `0 <= rotationGraceSeconds <= 300`.
- **Grace Window Evaluation**:
  - In `TenantTokenService::refresh` and `PlatformTokenService::refresh`, evaluate `isWithinGrace`:
    ```php
    $isWithinGrace = $status === TokenStatus::ROTATED->value
        && $rotatedAt !== null
        && ($this->clock->now()->getTimestamp() - $this->values->timestamp($rotatedAt)) <= $this->config->rotationGraceSeconds;
    ```
  - If a rotated refresh token is presented within 30 seconds of its rotation timestamp, verify the session and user/operator remain active. If active, issue a valid token pair for the active session and return 200 OK without compromising the session.
  - If presented after the grace window (> 30s), trigger `AuthFamilyCompromiseRequested` / `PlatformAuthFamilyCompromiseRequested` as a genuine reuse/replay attack.
- **Explicit Transaction Revocation Persistence**:
  - Added domain exceptions `AuthFamilyCompromiseRequested` and `PlatformAuthFamilyCompromiseRequested`.
  - Handled these exceptions outside the rolled-back refresh transaction, executing `compromiseTenantFamily` and `compromisePlatformFamily` in dedicated, committed transactions ensuring immutable revocation audit logging.

### 2.2 Frontend Cross-Tab Synchronization & Deduplication
- **Cross-Tab BroadcastChannel**:
  - In `resources/js/shared/api/authRefreshCoordinator.ts`, added persistent `BroadcastChannel` listener for `autoerp.auth_refresh`.
  - When any tab completes a refresh, it broadcasts `{ type: 'refreshed', accessToken }`. Peer tabs immediately update their in-memory token via `updateRefreshedSession` and track `lastRefreshedAt`.
- **Lock Acquisition Deduplication**:
  - Before executing `performRefresh`, `withCrossTabRefreshLock` checks if `(Date.now() - lastRefreshedAt) < 5000` and `context.accessToken` exists. If so, it returns the fresh token immediately, eliminating redundant network calls.
- **Exported In-Flight State**:
  - Exported `isRefreshInProgress(): boolean` to indicate when a refresh promise is active.

### 2.3 Frontend API Client Request & Response Interceptors
- **Request Interceptor Await**:
  - In `resources/js/shared/api/apiClient.ts`, if `!isPublicApiRequest(config.url) && isRefreshInProgress()`, await the in-flight `refreshAccessToken()` prior to dispatching, ensuring new requests are dispatched with the fresh Bearer token.
- **Response Interceptor Smart Retry**:
  - When handling a 401 response, check if `currentContext.accessToken` differs from the token used in `config.headers.Authorization`. If a new token was already acquired while the request was in flight, retry immediately without initiating an unnecessary second refresh.

---

## 3. Verification & Test Results

1. **New Integration Test Suite**:
   - Authored `tests/Feature/Auth/RefreshTokenRotationConcurrencyTest.php` covering:
     - `test_tenant_refresh_token_rotation_allows_concurrent_requests_within_grace_period`: Asserts two consecutive/concurrent calls with the same refresh token return 200 OK, both access tokens authenticate successfully against `/api/v1/auth/me`, and session remains active.
     - `test_tenant_refresh_token_rotation_detects_reuse_and_revokes_after_grace_period`: Asserts presenting a rotated token past the 30s grace window triggers HTTP 401 `AUTH_TOKEN_REVOKED` and permanently revokes the session with reason `refresh_token_reuse_detected`.
     - `test_platform_refresh_token_rotation_allows_concurrent_requests_within_grace_period`: Asserts platform operator concurrent refreshes work seamlessly within the grace window and authenticate against `/api/v1/platform/auth/me`.
   - **Result: 3 passed (30 assertions)** in 1.58s.

2. **Full Repository Backend Test Suite (`php artisan test`)**:
   - Executed across all 31 modules:
   - **Result: 1,342 tests passed (13,875 assertions)** in 196.87s, 0 failures, 0 regressions.

3. **Frontend Typecheck (`npm run typecheck`)**:
   - `tsc --noEmit`: **0 diagnostics / errors**.

4. **Frontend Vitest Test Suite (`npm test -- --run`)**:
   - Executed across all frontend modules:
   - **Result: 174 test files passed (727 tests passed)** in 313.20s, 0 failures.
