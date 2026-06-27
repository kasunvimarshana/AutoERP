# Frontend test runner and TypeScript 6 correction

Date: 2026-06-27

## Context

The core-foundation verification package still had two frontend release gates:

- the aggregate Vitest process could complete individual tests but fail to terminate reliably when using the native thread pool;
- TypeScript 6 rejected the deprecated `baseUrl` compiler option after the temporary deprecation suppression was removed.

Every one of the 47 frontend test files passed when executed independently. Aggregate diagnostics showed that the native thread/fork pools could retain Node web-stream resources created while jsdom test files imported Axios. This was a test-runtime isolation issue, not a product API failure. A separate Vehicle create test also resolved an in-flight promise outside React `act()`, producing a legitimate asynchronous state-update warning.

## Decisions

- Use Vitest's VM-isolated thread pool for jsdom tests instead of forcing process exit or suppressing open handles.
- Keep bounded test parallelism through one named worker-count constant.
- Remove TypeScript's deprecated `baseUrl` option and make the path target explicitly relative to `tsconfig.json`.
- Resolve the deferred Vehicle create request inside React `act()` and verify that the submitting state returns to idle.
- Do not add Axios shims, global browser API deletion, forced-exit flags, or application runtime workarounds.

## Changes

- `vitest.config.ts`
  - changed the pool from `threads` to `vmThreads`;
  - reduced parallel workers to the named `TEST_WORKER_COUNT` value;
  - retained file isolation, timeouts, mock restoration, and jsdom setup.
- `tsconfig.json`
  - removed deprecated `baseUrl`;
  - changed the alias target to `./resources/js/*`.
- `resources/js/modules/vehicle/VehiclePages.test.tsx`
  - resolved the deferred create promise inside `act()`;
  - waited for the create button to leave its loading state;
  - removed the React asynchronous-update warning without weakening the duplicate-submit assertion.

## Verification

- `npm run typecheck`: passed with zero diagnostics.
- `npm run lint`: passed with zero errors and zero warnings.
- `npm test`: 47 files and 161 tests passed; the process exited normally.
- `npm run build`: passed; 654 modules transformed.
- Production entry bundle: 464.00 kB, 142.85 kB gzip.
- Full application PHP syntax scan: 2,589 files passed with zero failures.

## Backend runtime boundary

Composer dependencies were attached only for verification. Laravel boot remains blocked in this execution environment because the PHP CLI lacks required native extensions, including `mbstring`, `bcmath`, and any PDO database driver. The configured Debian package repositories were unreachable, so the extensions could not be provisioned here. No source polyfill or validation bypass was introduced.
