# Invoice payment and badge regression verification

Reviewed the path-independent supplier payment action and invoice-specific settlement badge against the Finance invoice list, Purchase invoice list, GRN navigation, supplier payment creation, customer invoice settlement, and the shared status badge. The invoice UI change does not alter payment creation, posting, invoice balance updates, API contracts, or database records. The shared badge remains in use by non-invoice modules.

Verification: six focused frontend test files passed (23 tests), covering both invoice detail routes, payment permission and settlement cases, supplier invoice navigation, supplier payment form source loading, GRN navigation, and customer payment entry. `git diff --check` passed for the Invoice module. No source changes were needed during this review.
