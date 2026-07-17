# Frontend Toast and Lifecycle Test Alignment

**Date:** 2026-07-13  
**Branch:** `worktree-0.0.8`

## Context

The post-remediation verification run completed all Laravel tests successfully on SQLite and MySQL, and TypeScript, ESLint, and Vite build gates completed without errors. Vitest retained six failures caused by a mix of test-shell drift, one critical recovery-feedback visibility gap, stale source assertions, and an incorrect browser-location assertion under the shared memory router.

## Changes

- the shared `TestRouter` now mounts the same `AppToastContainer` used by the production app shell, so component tests can observe the intentional toast-first `ErrorAlert` and `SuccessAlert` behavior;
- `ProtectedRoute` explicitly renders its bootstrap error inline, preserving the support correlation reference on the session-recovery screen while retaining the global toast;
- invoice lifecycle source assertions now match the current governed cancellation fallback and the current invoice detail relation set, including `postingPlan`;
- the vehicle-service invoice handoff test now observes the memory router location instead of reading the unrelated browser `window.location`.

## Boundaries preserved

- toast-first notification behavior remains the shared default;
- no business API, invoice lifecycle, payment, posting, or navigation production contract was weakened;
- the auth recovery page is the only production surface changed because its support reference must remain persistently visible;
- tests were aligned to authoritative behavior rather than adding compatibility code to production modules.

## Verification status

Before this batch, the supplied local run reported:

- Laravel: 669 tests passed with 8,354 assertions;
- MySQL: 669 tests passed with 8,354 assertions;
- TypeScript: passed;
- ESLint: completed with 15 existing warnings and zero errors;
- Vite production build: passed;
- Vitest: six failures addressed by this batch.

The frontend test suite, typecheck, lint, and build must be rerun after pulling these commits. This environment reviewed the authoritative branch after each write but did not execute the local Node test suite.
