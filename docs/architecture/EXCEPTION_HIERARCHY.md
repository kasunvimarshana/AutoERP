# Exception Hierarchy Documentation

This document provides comprehensive documentation for the exception hierarchy in the enterprise ERP/CRM system.

## Overview

The exception hierarchy is designed to provide clear, consistent error handling across all modules. All exceptions follow clean architecture principles and provide structured error responses for API clients.

## Core Exception Classes

### Base Exception

**Location:** `modules/Core/Exceptions/DomainException.php`

**Description:** Base class for all domain-specific exceptions.

**Properties:**
- `$httpStatusCode` (int): HTTP status code (default: 500)
- `$errorCode` (string): API error code (default: 'DOMAIN_ERROR')
- `$context` (array): Additional context data

**Methods:**
- `getHttpStatusCode(): int` - Get HTTP status code
- `getErrorCode(): string` - Get error code
- `getContext(): array` - Get context data
- `setContext(array $context): self` - Set context data
- `addContext(string $key, mixed $value): self` - Add context data

**Usage:**
```php
throw new DomainException(
    'Custom error message',
    0,
    null,
    ['resource_id' => 123]
);
```

### ValidationException

**Location:** `modules/Core/Exceptions/ValidationException.php`

**HTTP Status:** 422 Unprocessable Entity

**Error Code:** `VALIDATION_ERROR`

**Description:** Thrown when input validation fails.

**Additional Properties:**
- `$errors` (array): Validation errors

**Methods:**
- `getErrors(): array` - Get validation errors
- `setErrors(array $errors): self` - Set validation errors

**Usage:**
```php
throw new ValidationException(
    'The given data was invalid.',
    [
        'email' => ['The email field is required.'],
        'name' => ['The name must be at least 3 characters.']
    ]
);
```

### AuthorizationException

**Location:** `modules/Core/Exceptions/AuthorizationException.php`

**HTTP Status:** 403 Forbidden

**Error Code:** `AUTHORIZATION_ERROR`

**Description:** Thrown when a user attempts to access a resource without proper authorization.

**Usage:**
```php
throw new AuthorizationException('You cannot access this resource.');
```

### NotFoundException

**Location:** `modules/Core/Exceptions/NotFoundException.php`

**HTTP Status:** 404 Not Found

**Error Code:** `NOT_FOUND`

**Description:** Thrown when a requested resource cannot be found.

**Usage:**
```php
throw new NotFoundException('The requested resource was not found.');
```

### ConflictException

**Location:** `modules/Core/Exceptions/ConflictException.php`

**HTTP Status:** 409 Conflict

**Error Code:** `CONFLICT`

**Description:** Thrown when a request conflicts with the current state of the resource.

**Usage:**
```php
throw new ConflictException(
    'A resource with this identifier already exists.',
    0,
    null,
    ['identifier' => 'USER-001']
);
```

### BusinessRuleException

**Location:** `modules/Core/Exceptions/BusinessRuleException.php`

**HTTP Status:** 422 Unprocessable Entity

**Error Code:** `BUSINESS_RULE_VIOLATION`

**Description:** Thrown when a business rule is violated.

**Additional Properties:**
- `$ruleName` (string|null): The name of the violated rule

**Methods:**
- `getRuleName(): ?string` - Get the violated rule name
- `setRuleName(string $ruleName): self` - Set the violated rule name

**Usage:**
```php
throw new BusinessRuleException(
    'Cannot delete organization with active users.',
    'organization_deletion',
    0,
    null,
    ['active_users' => 15]
);
```

## Tenant Module Exceptions

### TenantNotFoundException

**Location:** `modules/Tenant/Exceptions/TenantNotFoundException.php`

**Extends:** `NotFoundException`

**Error Code:** `TENANT_NOT_FOUND`

**Usage:**
```php
throw new TenantNotFoundException('Tenant with ID 123 was not found.');
```

### InvalidTenantException

**Location:** `modules/Tenant/Exceptions/InvalidTenantException.php`

**Extends:** `ValidationException`

**Error Code:** `INVALID_TENANT`

**Usage:**
```php
throw new InvalidTenantException(
    'Tenant data is invalid.',
    ['domain' => ['Domain is already taken.']]
);
```

### TenantIsolationException

**Location:** `modules/Tenant/Exceptions/TenantIsolationException.php`

**Extends:** `BusinessRuleException`

**HTTP Status:** 403 Forbidden

**Error Code:** `TENANT_ISOLATION_VIOLATION`

**Usage:**
```php
throw new TenantIsolationException(
    'Attempted to access data from another tenant.',
    'tenant_isolation',
    0,
    null,
    ['attempted_tenant_id' => 456]
);
```

### OrganizationNotFoundException

**Location:** `modules/Tenant/Exceptions/OrganizationNotFoundException.php`

**Extends:** `NotFoundException`

**Error Code:** `ORGANIZATION_NOT_FOUND`

**Usage:**
```php
throw new OrganizationNotFoundException('Organization with ID 789 was not found.');
```

### CircularReferenceException

**Location:** `modules/Tenant/Exceptions/CircularReferenceException.php`

**Extends:** `BusinessRuleException`

**Error Code:** `CIRCULAR_REFERENCE`

**Usage:**
```php
throw new CircularReferenceException(
    'Cannot set parent: circular reference detected.',
    'organization_hierarchy',
    0,
    null,
    ['organization_id' => 10, 'parent_id' => 5]
);
```

## Auth Module Exceptions

### InvalidCredentialsException

**Location:** `modules/Auth/Exceptions/InvalidCredentialsException.php`

**Extends:** `AuthorizationException`

**HTTP Status:** 401 Unauthorized

**Error Code:** `INVALID_CREDENTIALS`

**Usage:**
```php
throw new InvalidCredentialsException('The provided credentials are invalid.');
```

### TokenExpiredException

**Location:** `modules/Auth/Exceptions/TokenExpiredException.php`

**Extends:** `AuthorizationException`

**HTTP Status:** 401 Unauthorized

**Error Code:** `TOKEN_EXPIRED`

**Usage:**
```php
throw new TokenExpiredException('Your session has expired.');
```

### TokenInvalidException

**Location:** `modules/Auth/Exceptions/TokenInvalidException.php`

**Extends:** `AuthorizationException`

**HTTP Status:** 401 Unauthorized

**Error Code:** `TOKEN_INVALID`

**Usage:**
```php
throw new TokenInvalidException('The provided token is invalid or malformed.');
```

### TokenRevokedException

**Location:** `modules/Auth/Exceptions/TokenRevokedException.php`

**Extends:** `AuthorizationException`

**HTTP Status:** 401 Unauthorized

**Error Code:** `TOKEN_REVOKED`

**Usage:**
```php
throw new TokenRevokedException('This token has been revoked.');
```

### UserNotFoundException

**Location:** `modules/Auth/Exceptions/UserNotFoundException.php`

**Extends:** `NotFoundException`

**Error Code:** `USER_NOT_FOUND`

**Usage:**
```php
throw new UserNotFoundException('User with email user@example.com was not found.');
```

### PermissionDeniedException

**Location:** `modules/Auth/Exceptions/PermissionDeniedException.php`

**Extends:** `AuthorizationException`

**Error Code:** `PERMISSION_DENIED`

**Additional Properties:**
- `$permission` (string|null): The required permission

**Methods:**
- `getPermission(): ?string` - Get the required permission
- `setPermission(string $permission): self` - Set the required permission

**Usage:**
```php
throw new PermissionDeniedException(
    'You do not have permission to delete users.',
    'users.delete'
);
```

### MaxDevicesExceededException

**Location:** `modules/Auth/Exceptions/MaxDevicesExceededException.php`

**Extends:** `BusinessRuleException`

**Error Code:** `MAX_DEVICES_EXCEEDED`

**Additional Properties:**
- `$maxDevices` (int|null): The maximum allowed devices

**Methods:**
- `getMaxDevices(): ?int` - Get the maximum allowed devices
- `setMaxDevices(int $maxDevices): self` - Set the maximum allowed devices

**Usage:**
```php
throw new MaxDevicesExceededException(
    'You can only be logged in on 5 devices at once.',
    5,
    'max_devices'
);
```

## Product Module Exceptions

### ProductNotFoundException

**Location:** `modules/Product/Exceptions/ProductNotFoundException.php`

**Extends:** `NotFoundException`

**Error Code:** `PRODUCT_NOT_FOUND`

**Usage:**
```php
throw new ProductNotFoundException('Product with SKU ABC-123 was not found.');
```

### InvalidProductTypeException

**Location:** `modules/Product/Exceptions/InvalidProductTypeException.php`

**Extends:** `ValidationException`

**Error Code:** `INVALID_PRODUCT_TYPE`

**Additional Properties:**
- `$productType` (string|null): The invalid product type

**Methods:**
- `getProductType(): ?string` - Get the invalid product type
- `setProductType(string $productType): self` - Set the invalid product type

**Usage:**
```php
throw new InvalidProductTypeException(
    'Product type "unknown" is not supported.',
    'unknown',
    ['type' => ['Must be one of: goods, service, combo']]
);
```

### UnitConversionException

**Location:** `modules/Product/Exceptions/UnitConversionException.php`

**Extends:** `BusinessRuleException`

**Error Code:** `UNIT_CONVERSION_ERROR`

**Additional Properties:**
- `$fromUnit` (string|null): The source unit
- `$toUnit` (string|null): The target unit

**Methods:**
- `getFromUnit(): ?string` - Get the source unit
- `setFromUnit(string $fromUnit): self` - Set the source unit
- `getToUnit(): ?string` - Get the target unit
- `setToUnit(string $toUnit): self` - Set the target unit

**Usage:**
```php
throw new UnitConversionException(
    'Cannot convert from kilograms to liters.',
    'kg',
    'L',
    'unit_conversion'
);
```

### CategoryNotFoundException

**Location:** `modules/Product/Exceptions/CategoryNotFoundException.php`

**Extends:** `NotFoundException`

**Error Code:** `CATEGORY_NOT_FOUND`

**Usage:**
```php
throw new CategoryNotFoundException('Category with ID 456 was not found.');
```

## Pricing Module Exceptions

### PriceNotFoundException

**Location:** `modules/Pricing/Exceptions/PriceNotFoundException.php`

**Extends:** `NotFoundException`

**Error Code:** `PRICE_NOT_FOUND`

**Usage:**
```php
throw new PriceNotFoundException('No price found for this product and location.');
```

### InvalidPricingStrategyException

**Location:** `modules/Pricing/Exceptions/InvalidPricingStrategyException.php`

**Extends:** `ValidationException`

**Error Code:** `INVALID_PRICING_STRATEGY`

**Additional Properties:**
- `$strategy` (string|null): The invalid pricing strategy

**Methods:**
- `getStrategy(): ?string` - Get the invalid pricing strategy
- `setStrategy(string $strategy): self` - Set the invalid pricing strategy

**Usage:**
```php
throw new InvalidPricingStrategyException(
    'Pricing strategy "dynamic" is not supported.',
    'dynamic',
    ['strategy' => ['Must be one of: flat, percentage, tiered']]
);
```

### PricingCalculationException

**Location:** `modules/Pricing/Exceptions/PricingCalculationException.php`

**Extends:** `BusinessRuleException`

**Error Code:** `PRICING_CALCULATION_ERROR`

**Usage:**
```php
throw new PricingCalculationException(
    'Failed to calculate price: invalid pricing rules.',
    'pricing_calculation',
    0,
    null,
    ['product_id' => 123, 'quantity' => 10]
);
```

## Exception Handler

The global exception handler (`app/Exceptions/Handler.php`) automatically catches and formats domain exceptions for JSON responses.

### JSON Response Format

All domain exceptions are returned in a consistent JSON format:

```json
{
    "success": false,
    "error": {
        "code": "ERROR_CODE",
        "message": "Human-readable error message"
    }
}
```

### Additional Fields

Depending on the exception type, additional fields may be included:

**Validation Exceptions:**
```json
{
    "success": false,
    "error": {
        "code": "VALIDATION_ERROR",
        "message": "The given data was invalid.",
        "errors": {
            "email": ["The email field is required."],
            "name": ["The name must be at least 3 characters."]
        }
    }
}
```

**Business Rule Exceptions:**
```json
{
    "success": false,
    "error": {
        "code": "BUSINESS_RULE_VIOLATION",
        "message": "A business rule was violated.",
        "rule": "organization_deletion",
        "context": {
            "active_users": 15
        }
    }
}
```

**Exceptions with Context:**
```json
{
    "success": false,
    "error": {
        "code": "CONFLICT",
        "message": "A resource with this identifier already exists.",
        "context": {
            "identifier": "USER-001"
        }
    }
}
```

## Best Practices

### 1. Choose the Appropriate Exception

Use the most specific exception type available:
- Use `NotFoundException` when a resource doesn't exist
- Use `ValidationException` for input validation errors
- Use `AuthorizationException` for permission issues
- Use `ConflictException` for duplicate resources or concurrent updates
- Use `BusinessRuleException` for business logic violations

### 2. Provide Clear Messages

Exception messages should be:
- Clear and descriptive
- User-friendly (avoid technical jargon)
- Actionable (help users understand how to fix the issue)

**Good:**
```php
throw new ProductNotFoundException('Product with SKU "ABC-123" was not found.');
```

**Bad:**
```php
throw new ProductNotFoundException('Product not found'); // Too vague
```

### 3. Include Relevant Context

Add context data to help with debugging and provide additional information:

```php
throw new TenantIsolationException(
    'Attempted to access data from another tenant.',
    'tenant_isolation',
    0,
    null,
    [
        'current_tenant_id' => $currentTenantId,
        'attempted_tenant_id' => $attemptedTenantId,
        'resource_type' => 'Product',
        'resource_id' => $productId
    ]
);
```

### 4. Use Specific Error Codes

Error codes should be:
- Unique across the system
- Descriptive (SNAKE_CASE)
- Consistent with the exception type

### 5. Set Appropriate HTTP Status Codes

HTTP status codes should follow REST conventions:
- `400` - Bad Request (client error)
- `401` - Unauthorized (authentication required)
- `403` - Forbidden (authenticated but not authorized)
- `404` - Not Found (resource doesn't exist)
- `409` - Conflict (duplicate resource, concurrent update)
- `422` - Unprocessable Entity (validation error, business rule violation)
- `500` - Internal Server Error (unexpected server error)

### 6. Don't Report Expected Exceptions

The exception handler is configured not to report certain exceptions to logs:
- `ValidationException` - Expected user input errors
- `AuthorizationException` - Expected permission issues
- `NotFoundException` - Expected when resources don't exist

These exceptions are still returned to the client but won't clutter logs.

### 7. Chain Exceptions

Use exception chaining to preserve the original error context:

```php
try {
    $result = $this->externalService->call();
} catch (\Exception $e) {
    throw new PricingCalculationException(
        'Failed to calculate price due to external service error.',
        'pricing_calculation',
        0,
        $e  // Chain the original exception
    );
}
```

## Testing Exceptions

When writing tests, verify both the exception type and its properties:

```php
public function test_throws_product_not_found_exception()
{
    $this->expectException(ProductNotFoundException::class);
    $this->expectExceptionMessage('Product with SKU "ABC-123" was not found.');
    
    $product = $this->productRepository->findBySku('ABC-123');
}

public function test_exception_has_correct_http_status_code()
{
    $exception = new ProductNotFoundException('Test message');
    
    $this->assertEquals(404, $exception->getHttpStatusCode());
    $this->assertEquals('PRODUCT_NOT_FOUND', $exception->getErrorCode());
}
```

## Summary

The exception hierarchy provides:
- ✅ Consistent error handling across all modules
- ✅ Clear, structured API error responses
- ✅ Proper HTTP status codes
- ✅ Comprehensive context information
- ✅ Type safety and IDE support
- ✅ Easy testing and debugging
- ✅ Clean architecture compliance
- ✅ Production-ready error handling
