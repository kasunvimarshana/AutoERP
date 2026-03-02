# Auth Module

Handles JWT-based authentication, RBAC, and multi-device session management.

## Responsibilities

- User registration and login (JWT)
- Token refresh and invalidation
- Role and permission management (RBAC)
- Multi-tenant user isolation

## Architecture

```
Auth/
├── Domain/
│   ├── ValueObjects/Email.php         — Validated email VO
│   ├── ValueObjects/Password.php      — BCrypt password VO
│   ├── Entities/User.php              — Pure domain entity
│   └── Contracts/UserRepositoryInterface.php
├── Application/
│   ├── Commands/LoginCommand.php
│   ├── Commands/RegisterCommand.php
│   ├── Handlers/LoginHandler.php
│   └── Handlers/RegisterHandler.php
├── Infrastructure/
│   ├── Models/User.php                — Eloquent + JWTSubject
│   ├── Models/Role.php
│   ├── Models/Permission.php
│   ├── Repositories/UserRepository.php
│   └── Database/Migrations/
├── Interfaces/
│   └── Http/
│       ├── Controllers/AuthController.php
│       ├── Requests/LoginRequest.php
│       └── Resources/UserResource.php
└── Providers/AuthServiceProvider.php
```

## API Endpoints

| Method | Endpoint              | Auth | Description            |
|--------|-----------------------|------|------------------------|
| POST   | /api/v1/auth/login    | No   | Obtain JWT token       |
| POST   | /api/v1/auth/register | No   | Register a new user    |
| POST   | /api/v1/auth/logout   | Yes  | Invalidate token       |
| POST   | /api/v1/auth/refresh  | Yes  | Refresh JWT token      |
| GET    | /api/v1/auth/me       | Yes  | Get authenticated user |

## Security

- Passwords hashed with BCrypt (cost 12)
- JWT tokens with configurable TTL
- Token blacklisting on logout
- Per-tenant email uniqueness enforced at DB level
