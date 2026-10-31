# Add WhatsApp sharing after Vehicle Service payment

## Request

Add a WhatsApp share action to the Vehicle Service payment success state, beside the existing bill print action, while retaining the existing unverified-number confirmation.

## Changes

- Added `Share via WhatsApp` beside `Print bill` after a direct payment is successfully created.
- Reused the Invoice module's WhatsApp share endpoint and signed invoice PDF link/message flow.
- Preserved the existing confirmation prompt for recipients whose WhatsApp number is not verified.
- Added loading and error feedback for the share action. Payment creation and the existing print action remain unchanged.

## Verification

- `npm run typecheck` passed.
- `git diff --check` passed.
- Focused ESLint completed with no errors; it reported one existing React set-state-in-effect warning in unchanged code around invoice initialization.
- Automated tests were not run.
