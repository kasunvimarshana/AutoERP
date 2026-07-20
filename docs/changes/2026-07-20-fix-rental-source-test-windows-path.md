# Fix Vehicle Rental source-selection test path portability

## Problem

The Vehicle Rental source-selection Vitest suite read sibling source files through `new URL(..., import.meta.url)`. Under the Windows Vitest/Vite runtime, the transformed module URL was not a `file:` URL, so Node rejected it before either regression test could run.

## Change

The test now resolves both repository files from `process.cwd()` with Node's cross-platform `path.resolve()` API and passes normal filesystem paths to `readFileSync()`.

## Scope

Only the failing frontend contract test and this append-only record changed. Vehicle Rental production code, business rules, APIs, relationships, database schema, and runtime behavior are unchanged.
