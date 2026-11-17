# cPanel login token expiry diagnosis

**Date:** 2026-11-17  
**Scope:** Production diagnosis only; no application or hosting configuration changed.

The deployed tenant login endpoint returned HTTP 200 and issued a bearer token. Immediate authenticated requests to `/api/v1/auth/me`, organization-unit options, the reports dashboard, and pending approval count returned HTTP 401. An immediate refresh returned `AUTH_TOKEN_EXPIRED` with the message `The access token has expired.` The token response had an access expiry one hour after the server's current UTC time. This rules out invalid login credentials and points to token persistence/parsing, rather than the browser dashboard requests themselves.

`SystemClock` issues token timestamps in UTC. `AuthAccessTokenModel` casts database datetime values through Eloquent. A local reproduction with the Laravel/PHP application timezone set to `Asia/Colombo` showed that a UTC expiry stored as a timezone-free SQL datetime is read back as Colombo time, moving the effective expiry 5 hours 30 minutes earlier. A one-hour access token is therefore considered expired immediately. The production `APP_TIMEZONE` value and database connection timezone have not been read, so this is a strongly supported deployment diagnosis, not a direct inspection of hosting configuration.

The deployment owner should inspect the effective cached `app.timezone` and database datetime timezone. The immediate corrective configuration is UTC for application and persisted auth timestamps, with `localization.timezone` configured separately for the workspace's business calendar. Clear/rebuild the Laravel configuration cache after changing the deployment configuration, then sign in again and verify `/api/v1/auth/me` and the dashboard. Avoid changing token TTL or bypassing authentication to mask the issue.

No credentials, tokens, or private response data are recorded here. No source, database, or production files were modified during this investigation.
