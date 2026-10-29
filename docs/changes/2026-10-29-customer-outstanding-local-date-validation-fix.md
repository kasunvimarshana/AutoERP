# Customer outstanding local date validation fix

Date: 2026-10-29

The Customer Outstanding report previously defaulted its as-at date from the browser's local calendar while Laravel's `before_or_equal:today` validation used the UTC application calendar. Near midnight UTC, a legitimate local date such as 2026-10-29 in Sri Lanka was rejected as a future date.

The report now sends the active business timezone (or the browser's IANA timezone when no workspace timezone is configured) with the as-at date. The Finance request validates that timezone and compares the date with today in the same timezone. Requests that omit timezone retain the application timezone fallback. The report service's omitted-date default and CSV filename use the same request date rule. The UI creates its initial/reset filters at render time and uses the business date utility for its default, max, and fallback dates. Historical balance calculations and data were not changed. No migration is required.

Verification: the focused Finance boundary test passed with six assertions, covering Colombo today, future date rejection, UTC comparison, and invalid timezone rejection. The Customer Outstanding page test passed and confirmed that the displayed 2026-10-29 date and Asia/Colombo timezone are sent together. Business date utility tests passed. TypeScript typecheck, PHP syntax, Pint, and diff checks passed. Targeted ESLint found zero errors and only the page's two pre-existing synchronous-effect warnings.
