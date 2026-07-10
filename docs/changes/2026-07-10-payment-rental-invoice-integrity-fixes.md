# Payment and Rental Invoice Integrity Fixes

## Summary

- Realized pending payment allocations before marking payment posting as in progress or posting to Finance.
- Rejected rental invoice requests that include calculation line IDs outside the locked calculation run.

## Root cause

Payment posting previously posted to Finance before pending invoice allocations were realized. Although the work happened inside a database transaction, the lifecycle order made Finance posting precede final settlement realization.

Rental invoice generation filtered selected calculation line IDs with `whereIn`, so a request containing a mix of valid and invalid line IDs could silently ignore the invalid IDs instead of failing explicitly.

## Design notes

- The Payment module owns payment posting lifecycle order, so the allocation realization order was fixed in `PaymentPostingService`.
- The Vehicle Rental module owns calculation-run line selection, so selected line validation was added in `RentalInvoiceIntegrationService`.
- No compatibility overloads, schema changes, or unrelated refactors were introduced.

## Verification

- Reviewed the updated payment posting sequence on the branch and confirmed allocation realization now happens before the posting-in-progress state and Finance posting.
- Reviewed the updated rental invoice selection path on the branch and confirmed invalid calculation line IDs now fail explicitly before filtering selected lines.
- Full local Laravel/PHPUnit and frontend runtime verification was not available in this connector-only change pass.
