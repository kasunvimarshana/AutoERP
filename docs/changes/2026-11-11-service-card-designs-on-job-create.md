# Offer all valid service card designs on job creation

The job promotion selector previously received only card designs linked to count offers. It now receives valid direct benefit designs as well as count and mixed designs. Count designs still require active, currently valid offers and exclude an existing active or paused assignment for the selected vehicle. The selector is labeled Service card, and a newly issued card is selected immediately, even when the customer already has another card.

Card issuance still uses the Promotion module's existing API, permission check, and customer and vehicle validation. Issuing a card remains an explicit action independent of saving the job; direct benefit lines are added only when the operator chooses the existing Add available card items action. Added backend coverage for direct design availability and UI coverage for direct issuance and selecting the newly issued card.
