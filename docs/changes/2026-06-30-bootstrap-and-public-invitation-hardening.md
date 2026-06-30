# Bootstrap and public invitation hardening

Date: 2026-06-30

## Root cause

The application could be started with stale cached configuration or a process-level environment that resolved an empty `APP_KEY`, even while another long-running process used a valid key. This produced split-brain runtime behavior: queue delivery could succeed while web requests failed with `MissingAppKeyException`.

The setup script also regenerated `APP_KEY` unconditionally, which made rerunning setup capable of rotating the application encryption key and invalidating encrypted state.

## Corrections

- Added `app:key:ensure`, which generates `APP_KEY` only when the selected environment file has no key.
- Existing valid keys are preserved.
- Existing invalid keys fail explicitly and are never overwritten automatically.
- `composer dev` now clears stale configuration and runs Auth readiness before starting server, queue, logs, and Vite.
- Auth readiness reports the effective environment filename, cached/uncached state, and a non-secret key fingerprint.
- `.env.example` documents `APP_PREVIOUS_KEYS` for deliberate key rotation.

## Public invitation security

- Invitation pages read tokens only from URL fragments.
- Fragment tokens are removed from browser chrome before paint.
- Query-string tokens are rejected and scrubbed while unrelated query parameters are preserved.
- Public login, invitation, refresh, and MFA-enrollment requests never receive stored bearer or tenant-context headers.
- Public endpoint failures never trigger authenticated token refresh.
- Backend request validation enforces the exact token alphabet and encoded length for both initial-administrator and platform-operator invitations.

## Security boundaries retained

- No fallback or hardcoded encryption key was introduced.
- Missing-key validation remains strict.
- No exception is swallowed to allow an unconfigured application to continue.
- No database relationships or deletion behavior were changed without evidence of an invalid business relationship.

## Operational recovery

After deployment:

```bash
php artisan config:clear
php artisan auth:readiness --no-interaction
php artisan queue:restart
```

All application processes must report the same key fingerprint. Any invitation token exposed through logs, screenshots, recordings, or support channels must be revoked and replaced.
