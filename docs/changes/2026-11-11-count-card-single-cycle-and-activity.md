# Count card single-cycle redemption and activity

Count card assignments now earn one free reward after the configured paid quantity is posted on invoices. Further paid invoices do not start another cycle on that assignment. Posting the final free service completes the assignment and card. A new card can be manually issued from the still-valid design to start a fresh count; expired designs and cards are not offered for use. Standalone repeat offers retain their existing repeat behavior.

The job promotion strip lists valid count card designs for controlled assignment. The promotion workspace now shows each card's count progress, free uses, effective status, and linked paid/free invoice events. Invoice reversals append correction events and recalculate progress. If a newer assignment is already active, a reversed older completed assignment stays closed and is marked for admin review.

The existing promotion tables and invoice costing flow remain the source of truth. A free service is invoiced with a full promotion discount; no separate promotion expense entry or service-cost estimate was added. Existing stock cost accounting continues in its owning module.

Verified with focused Vehicle Service PHPUnit tests, the Promotion job strip Vitest, TypeScript typecheck, ESLint, Pint, and diff whitespace check.
