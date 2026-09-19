# Free WhatsApp Number Ownership Verification — Finalized Plan

## Status

Finalized for later implementation.

This document defines the free, API-free workflow for manually verifying the WhatsApp numbers stored for customers and suppliers. It complements the planned **Share via WhatsApp** document-sharing feature; it does not replace the existing PDF download action.

## Decision

AutoERP will use an employee-assisted verification-code exchange through WhatsApp Click to Chat.

- No WhatsApp Cloud API or paid third-party number-lookup service is required.
- AutoERP generates a short-lived verification code and opens WhatsApp with a pre-filled message to the selected recipient number.
- The customer or supplier replies with that code from the same WhatsApp conversation.
- An authorized employee compares the sender number, enters the returned code in AutoERP, and explicitly confirms that the reply came from the same number.
- AutoERP records the result as **Manually verified**, including the exact normalized phone number and the employee who verified it.

This is an auditable staff confirmation, not automatic cryptographic proof. Because AutoERP does not read the incoming WhatsApp message, it cannot independently prove who sent the reply.

## Goals

- Provide a no-subscription, no-message-API verification method.
- Confirm that the recipient can receive and reply through the entered WhatsApp number.
- Use the same behavior for customer and supplier numbers.
- Prevent an old verification from remaining valid after a phone-number change.
- Preserve a clear audit trail without modifying historical attempts.
- Keep the normal bill/PO PDF download option unchanged.

## Out of Scope

- Automatically reading WhatsApp replies.
- Automatically checking whether arbitrary numbers are registered on WhatsApp.
- Automatically sending verification or document messages.
- WhatsApp Business Platform webhooks, templates, billing, or chatbot behavior.
- Scraping WhatsApp Web or using unofficial WhatsApp automation libraries.

## User Flow

### 1. Start verification

On the customer or supplier screen, show the WhatsApp number together with:

- Status badge: **Unverified**, **Pending**, **Manually verified**, or **Number changed — reverify**.
- Primary action for unverified numbers: **Verify in WhatsApp**.
- Verification metadata for verified numbers: verified date and verifier name.

The action must be disabled when there is no usable WhatsApp number. The UI must explain whether the selected number came from the entity's primary contact or its own WhatsApp/mobile field.

### 2. Normalize and validate

The backend is the source of truth for phone-number validation and normalization.

- Convert the entered number to the project's canonical international representation.
- Build the Click to Chat number without spaces, `+`, brackets, or dashes.
- Reject missing, malformed, or unsupported numbers with a clear error.
- Bind the verification attempt to the exact normalized number and the exact customer/supplier record.

Frontend validation may provide immediate feedback but must not replace backend validation.

### 3. Generate the challenge

The backend creates a cryptographically random, single-use verification code.

Recommended defaults, stored as named configuration rather than scattered literals:

- Code lifetime: 15 minutes.
- Maximum failed entry attempts: 5.
- Only one active challenge for the same entity and normalized number.

Creating a new challenge atomically supersedes any previous pending challenge for that same entity and number. Store only a secure hash of the code where practical.

### 4. Open WhatsApp

AutoERP opens the official Click to Chat URL for the selected number with a pre-filled message similar to:

```text
AutoERP WhatsApp number verification

Please reply to this chat with the following verification code:
WA-7K4P2M

This code expires in 15 minutes.
```

The employee reviews the recipient and manually presses **Send** in WhatsApp. AutoERP must never claim that opening the link means the message was sent.

### 5. Receive the reply

The customer or supplier replies with the same code. The employee must verify in WhatsApp that the reply came from the same number being verified.

### 6. Confirm in AutoERP

The employee returns to the pending verification dialog and:

1. Enters the returned code.
2. Selects the required confirmation: **I confirmed that this reply came from the same WhatsApp number**.
3. Submits the verification.

The backend atomically validates:

- The authenticated employee has permission to verify the relevant customer or supplier.
- The challenge belongs to that entity and exact normalized phone number.
- The challenge is pending, unexpired, and unused.
- The failed-attempt limit has not been reached.
- The submitted code matches.
- The stored number has not changed since the challenge was created.

On success, consume the challenge once and create an immutable successful verification record.

## Status Rules

- **Unverified**: no successful verification exists for the current normalized number.
- **Pending**: a valid, unexpired challenge exists for the current normalized number.
- **Manually verified**: the latest successful record matches the current normalized number.
- **Number changed — reverify**: the number has changed since the last successful verification.

Verification belongs to the exact number, not merely to the customer or supplier. Changing, clearing, or replacing that number immediately makes the previous verification inapplicable. Do not edit the historical record; derive the current status by comparing its phone snapshot with the current normalized number.

## Document-Sharing Behavior

- Keep the existing **Download PDF** action unchanged.
- Place **Share via WhatsApp** beside it as planned.
- Recipient selection continues to use the relevant customer/supplier WhatsApp-number priority rules.
- Display the verification status beside the selected recipient.
- Default behavior: allow an authorized employee to share to an unverified number after a clear warning. This avoids blocking urgent work while still making the risk visible.
- A future business setting may require verified recipients before sharing, but it is not part of the initial free implementation.
- Verification does not change document-link lifetime, access control, or download auditing; those remain responsibilities of the document-sharing feature.

## Data and Audit Requirements

The exact table and model names must be finalized only after inspecting the current Customer, Supplier, Contact, User, and authorization structures. Do not guess or introduce foreign keys to the wrong owner.

The implementation must preserve, at minimum:

- Owning customer or supplier reference.
- Source contact reference when the selected number belongs to a contact.
- Normalized phone-number snapshot.
- Verification-code hash.
- Challenge creation and expiry timestamps.
- Attempt count and terminal outcome.
- Verification method: manual WhatsApp reply-code confirmation.
- Employee who created the challenge.
- Employee who confirmed the reply.
- Verification timestamp.
- Superseded, expired, failed, and successful outcomes as append-only history.

Use module-owned persistence with real foreign keys. Customer and Supplier schema changes must be kept in their responsible modules, and each database table must have its own explicit portable Laravel migration.

## Concurrency and Integrity

- Challenge creation, failed-attempt increments, and successful consumption must run inside database transactions.
- Lock or version-check the relevant active challenge so two employees cannot consume it simultaneously.
- A successful challenge can be consumed only once.
- Recheck the current normalized number during confirmation to prevent verifying a number that changed in another session.
- Duplicate clicks must be idempotent or return the already-completed result without creating conflicting success records.
- Historical records must never be overwritten to represent later state changes.

## Security and Permissions

- Only authenticated employees with the appropriate customer/supplier update permission may start or complete verification.
- Never expose verification-code hashes or internal identifiers in the UI.
- Do not log plaintext verification codes.
- Rate-limit challenge creation and code submissions.
- Escape and URL-encode all pre-filled message content.
- Record security-relevant outcomes explicitly; do not silently ignore failures.
- Do not use unofficial APIs, browser automation, or scraping to detect WhatsApp registration.

## Error Handling

Provide specific messages for:

- Missing or invalid WhatsApp number.
- WhatsApp could not be opened.
- Incorrect code and remaining attempts.
- Expired, superseded, or already-used code.
- Number changed during verification.
- Permission denied.
- Concurrent completion by another employee.

Opening WhatsApp and sending a message are separate actions. If WhatsApp opens but the employee does not send the message, the challenge remains pending until it expires or is superseded.

## Suggested UI Copy

- Button: **Verify in WhatsApp**
- Confirmation dialog title: **Confirm WhatsApp reply**
- Code input: **Verification code received from the customer/supplier**
- Required checkbox: **I confirmed that this reply came from the same WhatsApp number.**
- Success badge: **Manually verified**
- Changed-number badge: **Number changed — reverify**
- Warning before an unverified share: **This recipient number has not been manually verified. Confirm the number before continuing.**

## Acceptance Criteria

1. An authorized employee can start verification from both customer and supplier records.
2. The correct recipient number is normalized and preselected in WhatsApp Click to Chat.
3. The message contains a unique, single-use, expiring verification code.
4. A wrong, expired, superseded, used, or over-attempted code cannot verify a number.
5. Successful confirmation records the exact phone snapshot, timestamp, method, and employee.
6. Changing the stored number removes the current verified status without deleting history.
7. Concurrent confirmation cannot create conflicting successful results.
8. Unauthorized users cannot start or complete verification.
9. The UI calls the outcome **Manually verified** and does not represent it as automatic WhatsApp/API verification.
10. Existing PDF download remains unchanged, and Share via WhatsApp can clearly display the recipient's verification state.
11. Backend tests cover customer and supplier success, invalid code, expiry, number change, authorization, retry limit, and concurrent/double submission.
12. Frontend tests cover status display, required manual confirmation, warnings, validation errors, and WhatsApp-link generation.

## Cost and Limitations

- AutoERP implementation and hosting costs aside, this flow has no WhatsApp API message fee.
- It requires employee involvement and a reply from the customer or supplier.
- AutoERP cannot automatically detect the incoming reply or independently prove WhatsApp account ownership without integrating a supported messaging API.
- A verified number may later be deactivated or reassigned; the recorded status means it was manually confirmed at the displayed time.

## Deferred Upgrade Path

If fully automatic verification becomes necessary later, replace only the transport and reply-confirmation portion with an official WhatsApp Business Platform integration and webhook. Keep normalization, challenge rules, audit history, authorization, status semantics, and number-change invalidation as the stable domain foundation.

