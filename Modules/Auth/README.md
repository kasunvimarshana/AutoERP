# Auth Module
Handles authentication, token issuance and lifecycle using Laravel Sanctum.
## Features
- Login / logout / register
- Sanctum token issuance per device
- Auth guard for protected routes
## Routes
- POST /api/v1/auth/login
- POST /api/v1/auth/register
- POST /api/v1/auth/logout (auth:sanctum)
- GET /api/v1/auth/me (auth:sanctum)
