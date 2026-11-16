# Match the service card and offer assignment layout

Reworked the service job promotion panel around the supplied two-column reference. Active count offers and assignable count offers appear in the left panel with paid progress, a compact visit indicator for small whole-number targets, free-ready status, and the existing paid/free line action. The right panel keeps card design selection, validity, immediate-assignment guidance, and available design names together. After assignment, the selected card and its direct benefit items appear below with an individual Add line action.

The panels stack on narrow screens. Existing Promotion APIs, permissions, assignment rules, and Vehicle Service line mutations remain the source of truth. Card assignment still saves immediately; item lines are added only when an operator chooses their action. Updated focused UI tests for the layout and existing card and offer actions.
