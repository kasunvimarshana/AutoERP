# Auth Module

Handles multi-tenant authentication using Laravel Sanctum API tokens.

## Architecture

Follows the **Controller → Handler → Repository → Entity** pattern.

- **Domain Layer**: `User` entity, `UserRepositoryInterface` (includes `verifyPassword()`, `createAuthToken()`, `revokeTokenByBearerString()`), `UserStatus` enum
- **Application Layer**: `RegisterUserCommand/Handler`, `LoginCommand/Handler`, `LogoutCommand/Handler`
- **Infrastructure Layer**: `UserModel` (Eloquent + `HasApiTokens`), `UserRepository` (all Sanctum/Eloquent access encapsulated here)
- **Interface Layer**: `AuthController` (injects handlers only; no direct Eloquent), `RegisterRequest`, `LoginRequest`, `UserResource`

## API Endpoints

| Method | URI | Auth | Description |
|--------|-----|------|-------------|
| POST | `/api/v1/auth/register` | No | Register a new user |
| POST | `/api/v1/auth/login` | No | Login and get token |
| POST | `/api/v1/auth/logout` | Bearer | Revoke current token |
| GET | `/api/v1/auth/me` | Bearer | Get authenticated user |

## Multi-Tenancy

Every user is scoped to a `tenant_id`. Authentication is always tenant-aware:
- Login requires `tenant_id` in the request
- Tokens are scoped per user-device pair
- All user queries filter by `tenant_id` in `UserRepository`
