# Owner-bound service card items

Issued cards remain tied to the customer and vehicle selected at issue time. When the current customer ownership ends or is replaced, Vehicle emits an ownership event within its transaction; Promotion ends the former owner's active, paused, or exhausted cards and linked count assignments, increments their versions, and appends card lifecycle records. An ended card cannot be reactivated, even after invoice reversal. A later owner receives no card automatically; an administrative physical-card reassignment flow is reserved for a later phase.

The service-job card strip now shows an active checked indicator and one **Add available card items** action. It adds one job line for each direct benefit that is valid today and still has uses, leaving already exhausted benefits out. The initial line quantity remains one and staff can adjust it in the draft; the backend permits positive whole-number quantities up to the remaining uses. Existing earned repeat rewards retain their one-use-per-line rule.

Posting an invoice records the original redemption quantities and marks a direct-benefit card exhausted once all its uses are consumed. Invoice reversal appends correcting redemption events and restores the card when uses become available again. Ended and exhausted cards are absent from job eligibility; the management UI explains their status instead of offering invalid lifecycle actions.

Verification: seven focused card/offer integration tests passed (22 assertions), including new owner-change and two-use exhaustion cases. TypeScript typecheck, PHP syntax checks, Pint, and `git diff --check` passed.
