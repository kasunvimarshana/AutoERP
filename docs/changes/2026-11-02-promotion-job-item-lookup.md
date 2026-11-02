# Promotion job item lookup

The Service Cards & Offers form previously used the Item module's service-only lookup. Its paid item, reward item, and direct card benefit selectors now use Promotion's searchable, paginated lookup backed by the Vehicle Service job item service. They open with available options and display item code and name, like Purchase Order item selection. Search text is sent to the lookup endpoint to narrow results. Promotion's endpoint accepts an empty search so the initial list opens immediately.

Promotion setup now accepts the item types supported by Vehicle Service job lines. The backend uses Vehicle Service's item eligibility rule while retaining tenant, branch, and active-item validation. The job's promotion strip resolves reward items through the same lookup before adding a line. Batch options submit the underlying item ID for offer and card definitions.

The Promotion-owned lookup accepts users with promotion view, manage, or redeem permission and still requires Item view authorization. Existing Vehicle Service job lookup permissions were not changed.

Verification: PHP syntax checks, TypeScript parsing of the changed files, and `git diff --check` passed. A focused promotion item-type and empty-search test was added. The Laravel test command, route listing, and full TypeScript typecheck did not complete in this environment, so those checks remain to be rerun.
