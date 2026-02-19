# Authentication Module Documentation

## Overview

The Authentication module provides a complete, production-ready authentication system with support for:

- User registration with validation
- Login with token generation (Laravel Sanctum)
- Logout (single device or all devices)
- Token refresh
- Password reset flow
- Email verification
- Rate limiting to prevent brute force attacks
- Comprehensive audit logging
- RBAC/ABAC with roles and permissions

## Architecture

The module follows the **Controller → Service → Repository** pattern:

```
AuthController (HTTP Layer)
    ↓
AuthService (Business Logic Layer)
    ↓
AuthRepository (Data Access Layer)
    ↓
User Model (Domain Layer)
```

### Supporting Components

- **Request Classes**: Validate input data
- **Resource Classes**: Transform response data
- **Middleware**: Rate limiting for auth endpoints
- **Audit Logger**: Immutable logging of auth events
- **Seeders**: Default roles and permissions

## API Endpoints

### Public Endpoints (No Authentication Required)

#### 1. Register User
```http
POST /api/v1/auth/register
Content-Type: application/json

{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "SecurePass123!",
  "password_confirmation": "SecurePass123!",
  "role": "user" // Optional, defaults to "user"
}
```

**Response (201 Created):**
```json
{
  "success": true,
  "message": "Registration successful. Welcome!",
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "email_verified_at": null,
      "created_at": "2024-01-21T10:00:00.000000Z",
      "roles": ["user"],
      "permissions": ["user.read", "tenant.read"]
    },
    "token": "1|abc123...xyz"
  }
}
```

#### 2. Login
```http
POST /api/v1/auth/login
Content-Type: application/json

{
  "email": "john@example.com",
  "password": "SecurePass123!",
  "revoke_other_tokens": false // Optional
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Login successful. Welcome back!",
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "roles": ["user"],
      "permissions": ["user.read", "tenant.read"]
    },
    "token": "2|def456...uvw"
  }
}
```

#### 3. Forgot Password
```http
POST /api/v1/auth/forgot-password
Content-Type: application/json

{
  "email": "john@example.com"
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Password reset link sent to your email.",
  "data": null
}
```

#### 4. Reset Password
```http
POST /api/v1/auth/reset-password
Content-Type: application/json

{
  "token": "reset-token-from-email",
  "email": "john@example.com",
  "password": "NewSecurePass123!",
  "password_confirmation": "NewSecurePass123!"
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Password reset successfully.",
  "data": null
}
```

#### 5. Verify Email
```http
GET /api/v1/auth/verify-email/{id}/{hash}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Email verified successfully.",
  "data": null
}
```

### Protected Endpoints (Authentication Required)

**Authorization Header Required:**
```
Authorization: Bearer {your-token-here}
```

#### 6. Get Current User
```http
GET /api/v1/auth/me
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "User information retrieved successfully.",
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "roles": ["user", "manager"],
      "permissions": ["user.read", "user.create", "tenant.read"]
    },
    "token": null
  }
}
```

#### 7. Logout (Current Device)
```http
POST /api/v1/auth/logout
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Logged out successfully.",
  "data": null
}
```

#### 8. Logout All Devices
```http
POST /api/v1/auth/logout-all
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Logged out from all devices successfully.",
  "data": null
}
```

#### 9. Refresh Token
```http
POST /api/v1/auth/refresh
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Token refreshed successfully.",
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com"
    },
    "token": "3|ghi789...rst"
  }
}
```

#### 10. Resend Email Verification
```http
POST /api/v1/auth/resend-verification
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Verification link sent to your email.",
  "data": null
}
```

## Rate Limiting

Authentication endpoints are protected by rate limiting to prevent brute force attacks:

- **Login**: 5 attempts per minute per IP/email combination
- **Register**: 5 attempts per minute per IP
- **Password Reset**: 5 attempts per minute per IP

When rate limit is exceeded:
```json
{
  "success": false,
  "message": "Too many attempts. Please try again in 60 seconds.",
  "retry_after": 60
}
```

Response includes headers:
```
X-RateLimit-Limit: 5
X-RateLimit-Remaining: 0
```

## Security Features

### 1. Password Requirements

Passwords must meet these requirements:
- Minimum 8 characters
- Contains at least one uppercase letter
- Contains at least one lowercase letter
- Contains at least one number
- Contains at least one special character

### 2. Token Security

- Tokens are generated using Laravel Sanctum
- Tokens are stored hashed in the database
- Tokens can be revoked individually or all at once
- Tokens expire based on configuration

### 3. Audit Logging

All authentication events are logged to `storage/logs/auth.log`:

```
[2024-01-21 10:00:00] auth.login.success: {"user_id": 1, "ip": "192.168.1.1", "action": "login"}
[2024-01-21 10:05:00] auth.login.failed: {"email": "test@example.com", "ip": "192.168.1.2", "reason": "invalid_credentials"}
[2024-01-21 10:10:00] auth.logout: {"user_id": 1, "ip": "192.168.1.1", "action": "logout"}
```

Logged events include:
- Successful/failed logins
- Registration
- Logout (single/all devices)
- Password reset requests/completions
- Email verification
- Token refresh
- Rate limit exceeded

### 4. RBAC/ABAC

Default roles created by seeder:

| Role | Description | Permissions |
|------|-------------|-------------|
| super-admin | Full system access | All permissions |
| admin | Administrative access | User, role, permission, tenant management (except create/delete) |
| manager | Management access | User management, view permissions |
| user | Standard user | Read own data, read tenant |
| guest | Minimal access | Read tenant only |

## Usage Examples

### Example 1: Complete Registration Flow

```php
// 1. Register
$response = Http::post('http://api.example.com/api/v1/auth/register', [
    'name' => 'Jane Doe',
    'email' => 'jane@example.com',
    'password' => 'SecurePass123!',
    'password_confirmation' => 'SecurePass123!',
]);

$token = $response->json('data.token');
$user = $response->json('data.user');

// 2. Use token for authenticated requests
$profile = Http::withToken($token)
    ->get('http://api.example.com/api/v1/auth/me');
```

### Example 2: Login and Access Protected Resources

```php
// 1. Login
$response = Http::post('http://api.example.com/api/v1/auth/login', [
    'email' => 'jane@example.com',
    'password' => 'SecurePass123!',
]);

$token = $response->json('data.token');

// 2. Access protected resource
$users = Http::withToken($token)
    ->get('http://api.example.com/api/v1/users');

// 3. Logout
Http::withToken($token)
    ->post('http://api.example.com/api/v1/auth/logout');
```

### Example 3: Password Reset Flow

```php
// 1. Request password reset
Http::post('http://api.example.com/api/v1/auth/forgot-password', [
    'email' => 'jane@example.com',
]);

// User receives email with reset link containing token

// 2. Reset password using token
Http::post('http://api.example.com/api/v1/auth/reset-password', [
    'token' => 'reset-token-from-email',
    'email' => 'jane@example.com',
    'password' => 'NewSecurePass123!',
    'password_confirmation' => 'NewSecurePass123!',
]);
```

## Testing

Run authentication tests:

```bash
# Run all auth tests
php artisan test --filter AuthApiTest

# Run specific test
php artisan test --filter test_user_can_login_with_valid_credentials
```

## Configuration

### Environment Variables

```env
# Sanctum Configuration
SANCTUM_STATEFUL_DOMAINS=localhost,127.0.0.1
SANCTUM_GUARD=web

# Session Configuration (if using stateful)
SESSION_DRIVER=redis
SESSION_LIFETIME=120

# Password Reset Configuration
PASSWORD_RESET_TIMEOUT=3600  # 1 hour
```

### Customizing Rate Limits

Update routes in `Modules/Auth/routes/api.php`:

```php
Route::middleware(['auth.rate.limiter:10,2'])->group(function () {
    // 10 attempts per 2 minutes
    Route::post('login', [AuthController::class, 'login']);
});
```

## Troubleshooting

### Issue: "Unauthenticated" Error

**Solution**: Ensure you're sending the Authorization header:
```
Authorization: Bearer {your-token}
```

### Issue: Rate Limit Exceeded

**Solution**: Wait for the retry_after time or clear rate limits:
```bash
php artisan cache:clear
```

### Issue: Email Verification Not Working

**Solution**: Configure mail settings in `.env`:
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your-username
MAIL_PASSWORD=your-password
```

## Best Practices

1. **Always use HTTPS in production** to protect tokens in transit
2. **Store tokens securely** on the client (e.g., httpOnly cookies)
3. **Implement token refresh** before token expiration
4. **Log out on sensitive actions** (e.g., password change)
5. **Monitor auth logs** for suspicious activity
6. **Use strong password policies**
7. **Enable email verification** for new registrations
8. **Implement 2FA** for high-security applications (future enhancement)

## Security Considerations

- Tokens are bearer tokens - anyone with the token can access the API
- Tokens should be kept secret and transmitted only over HTTPS
- Implement token expiration and refresh mechanisms
- Monitor failed login attempts for brute force attacks
- Use rate limiting on all authentication endpoints
- Store audit logs in a separate, append-only storage
- Regularly review and rotate tokens
- Implement account lockout after multiple failed attempts (future enhancement)

## Future Enhancements

- [ ] Two-Factor Authentication (2FA)
- [ ] OAuth2 integration (Google, Facebook, etc.)
- [ ] Single Sign-On (SSO)
- [ ] Account lockout mechanism
- [ ] IP whitelist/blacklist
- [ ] Device management (trust/untrust devices)
- [ ] Session management dashboard
- [ ] Advanced audit trail with diff tracking
