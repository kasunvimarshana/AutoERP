# API Documentation

This document provides information about accessing and using the ModularSaaS API documentation powered by Swagger/OpenAPI 3.0.

## Overview

The API documentation is automatically generated from code annotations using [L5-Swagger](https://github.com/DarkaOnLine/L5-Swagger), which integrates [swagger-php](https://github.com/zircote/swagger-php) into Laravel applications.

## Features

- **Complete API Documentation**: All endpoints documented with:
  - Request parameters and body schemas
  - Response schemas and examples
  - Authentication requirements
  - Error responses
  
- **Interactive Testing**: Try out API endpoints directly from the documentation interface

- **Security**: Bearer token authentication (Laravel Sanctum) documented and testable

- **Auto-Generated**: Documentation stays in sync with code changes

## Accessing the Documentation

### Local Development

1. Start the Laravel development server:
   ```bash
   php artisan serve
   ```

2. Open your browser and navigate to:
   ```
   http://localhost:8000/api/documentation
   ```

### Production

The API documentation is accessible at:
```
https://your-domain.com/api/documentation
```

## Generating Documentation

The OpenAPI specification is automatically generated from PHP annotations in the controllers.

### Manual Generation

To manually regenerate the documentation:

```bash
php artisan swagger:generate
```

This command:
- Scans all controller files for OpenAPI annotations
- Generates the OpenAPI 3.0 specification
- Saves the output to `storage/api-docs/api-docs.json`
- Suppresses non-critical warnings

### Auto-Generation

You can configure the documentation to be regenerated automatically:

1. Set `L5_SWAGGER_GENERATE_ALWAYS=true` in your `.env` file
2. Documentation will be regenerated on each request (suitable for development only)

For production, it's recommended to:
- Generate documentation during deployment
- Set `L5_SWAGGER_GENERATE_ALWAYS=false`
- Commit the generated `storage/api-docs/api-docs.json` file

## API Structure

### Base URL

```
/api
```

### Authentication

Most endpoints require authentication using Laravel Sanctum bearer tokens.

**Format:**
```
Authorization: Bearer {your-token-here}
```

**How to get a token:**
1. Register a new account: `POST /api/v1/auth/register`
2. Or login: `POST /api/v1/auth/login`
3. Use the `access_token` from the response in subsequent requests

### Available Endpoints

#### Authentication Module

- `POST /api/v1/auth/register` - Register a new user
- `POST /api/v1/auth/login` - Login and get token
- `POST /api/v1/auth/logout` - Logout current session
- `POST /api/v1/auth/logout-all` - Logout all sessions
- `GET /api/v1/auth/me` - Get authenticated user
- `POST /api/v1/auth/refresh` - Refresh authentication token
- `POST /api/v1/auth/forgot-password` - Request password reset
- `POST /api/v1/auth/reset-password` - Reset password with token
- `GET /api/v1/auth/verify-email/{id}/{hash}` - Verify email
- `POST /api/v1/auth/resend-verification` - Resend verification email

#### User Management Module

- `GET /api/v1/users` - List all users (paginated)
- `POST /api/v1/users` - Create a new user
- `GET /api/v1/users/{id}` - Get user by ID
- `PUT /api/v1/users/{id}` - Update user
- `DELETE /api/v1/users/{id}` - Delete user
- `POST /api/v1/users/{id}/assign-role` - Assign role to user
- `POST /api/v1/users/{id}/revoke-role` - Revoke role from user

## Response Structure

### Success Response

```json
{
    "success": true,
    "message": "Operation successful",
    "data": {
        // Response data here
    }
}
```

### Error Response

```json
{
    "success": false,
    "message": "Error message"
}
```

### Validation Error Response

```json
{
    "success": false,
    "message": "The given data was invalid.",
    "errors": {
        "field_name": [
            "Validation error message"
        ]
    }
}
```

## Testing the API

### Using Swagger UI

1. Navigate to the API documentation page
2. Click "Authorize" button at the top
3. Enter your bearer token in the format: `Bearer {token}`
4. Click "Authorize"
5. Now you can test any endpoint by clicking "Try it out"

### Using cURL

**Example: Register a new user**
```bash
curl -X POST "http://localhost:8000/api/v1/auth/register" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "SecurePassword123!",
    "password_confirmation": "SecurePassword123!"
  }'
```

**Example: Get authenticated user**
```bash
curl -X GET "http://localhost:8000/api/v1/auth/me" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {your-token-here}"
```

### Using Postman

1. Import the OpenAPI specification:
   - Download: `http://localhost:8000/api/documentation/json`
   - In Postman: File → Import → Paste the URL
   
2. Configure authentication:
   - Select a request
   - Go to "Authorization" tab
   - Type: Bearer Token
   - Token: {your-token}

## Adding Documentation to New Endpoints

When creating new API endpoints, add OpenAPI annotations to document them:

```php
/**
 * @OA\Post(
 *     path="/api/v1/example",
 *     summary="Example endpoint",
 *     description="Detailed description of what this endpoint does",
 *     operationId="exampleOperation",
 *     tags={"Example"},
 *     security={{"sanctum": {}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"field"},
 *             @OA\Property(property="field", type="string", example="value")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Success response",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string")
 *         )
 *     )
 * )
 */
public function exampleMethod(Request $request): JsonResponse
{
    // Method implementation
}
```

## Configuration

The L5-Swagger configuration is located at:
```
config/l5-swagger.php
```

Key settings:
- `routes.api`: Documentation UI route (default: `api/documentation`)
- `paths.annotations`: Directories to scan for annotations
- `paths.docs`: Output directory for generated JSON/YAML

## Troubleshooting

### Documentation not updating

1. Clear Laravel cache:
   ```bash
   php artisan cache:clear
   php artisan config:clear
   ```

2. Regenerate documentation:
   ```bash
   php artisan swagger:generate
   ```

### Annotations not being detected

Ensure:
- `doctrine/annotations` package is installed
- Annotations follow correct format
- Controller files are in the scan path (check `config/l5-swagger.php`)

### 404 on documentation route

1. Clear route cache:
   ```bash
   php artisan route:clear
   ```

2. Verify route exists:
   ```bash
   php artisan route:list | grep documentation
   ```

## References

- [OpenAPI 3.0 Specification](https://swagger.io/specification/)
- [Swagger-PHP Documentation](https://zircote.github.io/swagger-php/)
- [L5-Swagger GitHub](https://github.com/DarkaOnLine/L5-Swagger)
- [Laravel Sanctum](https://laravel.com/docs/sanctum)

## Support

For API-related questions or issues:
- Email: support@modularsaas.com
- GitHub Issues: [Project Repository](https://github.com/kasunvimarshana/ModularSaaS-LaravelVue/issues)
