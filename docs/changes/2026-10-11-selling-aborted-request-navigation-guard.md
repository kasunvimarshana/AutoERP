# Selling route request cancellation guard — 2026-10-11

## Root cause and scope

The Selling workspace reuses one React page for sale detail, sale register and return register. Its route/page/search effect already cancelled old requests during navigation and search, but still unconditionally committed list/detail results or error state from those requests. A late resolution or rejection (including the rejected abort of an Axios request) could overwrite the new route's data, pagination metadata or display a stale cancellation error.

The warehouse-source request effect in the same Selling module already checks `controller.signal.aborted` before applying results, which provides the established local pattern. No back-end business rule, pricing, inventory movement, authorization or financial posting is changed.

## Minimal correction

- Keep existing route dependencies and AbortController ownership.
- Check the existing controller signal before setting sale detail, sale/return list results, pagination or request errors. The previous request's cleanup always aborts the signal.
- Do not turn an aborted request into a visible error. Genuine failures from the active request remain visible.
- No new global cache, compatibility fallback, delayed retry, external dependency or duplicate cross-module logic.

## Verification evidence

The exact source patterns and replacement guards were inspected on integration candidate `373bee414a361d0f9b71133c64dabf9087a0df0a` before creating this commit. This is a source-level review; it is not an executed browser or Vitest regression test. The full candidate requires fresh test runs. GitHub issue #124 documents automatic Actions jobs failing before executing any test steps. No merge or production promotion is authorized by this focused change.
