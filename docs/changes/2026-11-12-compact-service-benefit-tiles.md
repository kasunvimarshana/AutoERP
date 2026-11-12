# Compact service benefit tiles on service jobs

Reworked the Promotion job panel into compact Count offers and Service cards columns. Assigned offers show paid-visit progress and their existing paid or free job-line action. Unassigned offers and available card designs use explicit Assign actions so assignment cannot be confused with adding a job line.

Each assigned card has an expandable available-item count. Its direct benefits show item name, discount, remaining uses, and an individual Add line action when usable today. Issuing a card opens its benefit list. Assignment remains an immediate Promotion API write; adding a line still uses the existing Vehicle Service flow and promotion choice. Updated focused component tests for the new interactions.
