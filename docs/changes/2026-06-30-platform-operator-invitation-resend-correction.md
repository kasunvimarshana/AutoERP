# Platform operator invitation resend correction

Date: 2026-06-30

## Problem

Platform operator invitation delivery is asynchronous. The previous resend implementation revoked the pending invitation, generated a different token, and queued another email. A delayed or out-of-order email could therefore arrive after its token had already been revoked and open the registration page with:

> This invitation is invalid or no longer available.

The worker also received only an invitation ID and selected the latest open delivery attempt at execution time. An older queued job could therefore claim a newer resend attempt instead of the attempt that created that job.

## Correct ownership model

An invitation owns one active registration capability. Delivery attempts are retryable transport records for that capability.

```text
Platform operator
→ one pending invitation and token
→ delivery attempt 1
→ delivery attempt 2
→ ...
```

Resending transport must not silently replace the active registration capability.

## Changes

- Resend reuses the current pending invitation and token when they are still available.
- Resend refreshes the invitation expiry and creates the next delivery attempt.
- Older queued, sending, or failed attempts are cancelled before the new attempt is created.
- A new token is issued only when the previous invitation expired or its delivery token is unavailable.
- Queue jobs contain both the invitation ID and exact delivery ID.
- Job uniqueness is delivery-attempt based while overlap serialization remains invitation based.
- A delayed old job cannot claim a newer delivery attempt.
- Known accepted, replaced, revoked, and expired tokens return actionable validation messages.
- The platform-operator UI now explains that resend keeps the active link valid; explicit revoke remains the operation that invalidates it.

## Verification

Regression coverage verifies:

- the invitation ID, token hash, and plaintext delivery token remain unchanged across resend;
- expiry and row versions advance;
- the previous delivery attempt is cancelled;
- the next attempt is queued with an incremented attempt number;
- the original link still passes invitation inspection;
- queue jobs target the exact delivery attempt;
- resend messaging no longer claims that previous copies are invalid.

No compatibility token format, validation bypass, or duplicate invitation state was introduced.
