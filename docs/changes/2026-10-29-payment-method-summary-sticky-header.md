# Payment Method Wise Summary sticky header

The daily summary table now has a bounded scroll area with column headers fixed at its top. This keeps payment method labels visible while reading later dates and preserves horizontal scrolling when columns exceed the available width. Printing removes the scroll limit so the full table remains available.

No report calculations, API responses, or stored data changed.

Verification: TypeScript typecheck and `git diff --check` passed. The existing report page test did not complete in the local environment and was stopped.
