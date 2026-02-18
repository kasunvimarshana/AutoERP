# Core Module Documentation

---

**⚠️ IMPLEMENTATION PRINCIPLE**: All components use native Laravel features. Zero third-party dependencies.

---

## Overview

The Core module provides foundational infrastructure for all other modules in the AutoERP system. It implements Clean Architecture, Domain-Driven Design (DDD), and SOLID principles using only native Laravel features.

## Directory Structure

```
Modules/Core/
├── Config/                    # Module configuration
├── Domain/                    # DDD base classes
│   ├── Entity.php            # Base entity with identity
│   ├── AggregateRoot.php     # Aggregate root with event management
│   └── ValueObject.php       # Abstract value object contract
├── Events/                    # Event infrastructure
│   └── DomainEvent.php       # Base domain event
├── Exceptions/                # Custom exception hierarchy
│   ├── DomainException.php   # Business rule violations
│   ├── NotFoundException.php # Resource not found (404)
│   ├── ValidationException.php # Validation failures (422)
│   ├── UnauthorizedException.php # Authorization failures (403)
│   └── ConflictException.php # Resource conflicts (409)
├── Http/
│   ├── Controllers/
│   │   └── BaseApiController.php # Base API controller
│   ├── Middleware/           # Request/response middleware
│   ├── Requests/             # Form request base classes
│   └── Resources/            # API resource transformers
├── Providers/                # Service providers
├── Repositories/             # Repository pattern base classes
├── Services/                 # Application services
├── Support/                  # Helper classes
│   ├── ApiResponse.php       # Standardized API responses
│   ├── ImageProcessor.php    # Native image processing (PHP GD)
│   └── QueryBuilder.php      # API query filtering
├── Traits/                   # Reusable traits
│   ├── Auditable.php        # Track created_by/updated_by
│   ├── HasAddresses.php     # Polymorphic addresses
│   ├── HasContacts.php      # Polymorphic contacts
│   ├── HasPermissions.php   # RBAC functionality
│   ├── HasUuid.php          # UUID primary keys
│   ├── LogsActivity.php     # Activity logging
│   ├── Sluggable.php        # Auto-generate slugs
│   ├── Tenantable.php       # Multi-tenant isolation
│   └── Translatable.php     # Multi-language support
└── ValueObjects/             # DDD value objects
    ├── Address.php          # Physical address
    ├── Currency.php         # Currency with symbol
    ├── Email.php            # Email with validation
    ├── Money.php            # Monetary values with precision
    └── PhoneNumber.php      # Phone number with validation
```

## Exception Hierarchy

### DomainException

Base exception for business rule violations:

```php
use Modules\Core\Exceptions\DomainException;

// Throw business rule violation
throw DomainException::businessRuleViolation(
    'Order total must be positive',
    'Cannot create order with negative total'
);

// Throw invalid state
throw DomainException::invalidState('Order', 'cancelled');

// Throw invariant violation
throw DomainException::invariantViolation('Order must have at least one line item');
```

### NotFoundException

Resource not found (404):

```php
use Modules\Core\Exceptions\NotFoundException;

// Entity not found
throw NotFoundException::entity(Customer::class, $customerId);

// Resource not found
throw NotFoundException::resource('Customer', $customerNumber);
```

### ValidationException

Validation failures (422):

```php
use Modules\Core\Exceptions\ValidationException;

// Multiple field errors
throw ValidationException::withErrors([
    'email' => ['Email is already in use'],
    'phone' => ['Invalid phone format']
]);

// Single field error
throw ValidationException::forField('email', 'Email is required');
```

### UnauthorizedException

Authorization failures (403):

```php
use Modules\Core\Exceptions\UnauthorizedException;

// Missing permission
throw UnauthorizedException::missingPermission('delete-customer');

// Tenant access violation
throw UnauthorizedException::tenantAccessViolation('Customer');

// Action not allowed
throw UnauthorizedException::actionNotAllowed('delete', 'Order');
```

### ConflictException

Resource conflicts (409):

```php
use Modules\Core\Exceptions\ConflictException;

// Duplicate resource
throw ConflictException::duplicate('Customer', 'email', 'john@example.com');

// Resource in use
throw ConflictException::inUse('Product', $productId);

// State conflict
throw ConflictException::stateConflict('Order', 'cancelled', 'confirmed');
```

## Value Objects

### Email

Immutable email with validation:

```php
use Modules\Core\ValueObjects\Email;

$email = Email::fromString('john@example.com');
echo $email->getValue();      // john@example.com
echo $email->getDomain();     // example.com
echo $email->getLocalPart();  // john

$email->equals($otherEmail);  // true/false
```

### PhoneNumber

Phone number with country code:

```php
use Modules\Core\ValueObjects\PhoneNumber;

$phone = PhoneNumber::fromString('+1234567890', 'US');
echo $phone->getValue();       // +1234567890
echo $phone->getCountryCode(); // US
echo $phone->format();         // Formatted display
```

### Currency

Currency with symbol and precision:

```php
use Modules\Core\ValueObjects\Currency;

$currency = Currency::fromCode('USD');
echo $currency->getCode();     // USD
echo $currency->getSymbol();   // $
echo $currency->getDecimals(); // 2
echo $currency->getName();     // US Dollar

// Helper methods
$usd = Currency::USD();
$eur = Currency::EUR();
$gbp = Currency::GBP();

// Check if currency is supported
Currency::isSupported('USD'); // true

// Get all currencies
$all = Currency::getAllCurrencies();
```

**Supported Currencies:**
USD, EUR, GBP, JPY, CNY, INR, AUD, CAD, CHF, SEK, NZD, SGD, HKD, NOK, KRW, TRY, RUB, BRL, ZAR

### Money

Precise monetary calculations using BCMath:

```php
use Modules\Core\ValueObjects\Money;
use Modules\Core\ValueObjects\Currency;

$price = Money::fromAmount(99.99, 'USD');
$tax = Money::fromAmount(10.00, 'USD');

// Arithmetic operations
$total = $price->add($tax);              // $109.99
$discount = $total->multiply(0.1);       // $10.99
$final = $total->subtract($discount);    // $99.00

// Comparisons
$total->isPositive();                    // true
$total->isNegative();                    // false
$total->isZero();                        // false
$total->greaterThan($price);             // true
$total->lessThan($price);                // false

// Formatting
echo $total->format();                   // $ 109.99
```

### Address

Physical address with validation:

```php
use Modules\Core\ValueObjects\Address;

$address = new Address(
    line1: '123 Main St',
    city: 'New York',
    postalCode: '10001',
    country: 'US',
    line2: 'Apt 4B',
    state: 'NY'
);

echo $address->getLine1();         // 123 Main St
echo $address->getCity();          // New York
echo $address->getFullAddress();   // 123 Main St, Apt 4B, New York, NY, 10001, US

// Create from array
$address = Address::fromArray([
    'line1' => '123 Main St',
    'city' => 'New York',
    'postal_code' => '10001',
    'country' => 'US'
]);

// Serialize to array
$data = $address->toArray();
```

## Domain-Driven Design Base Classes

### Entity

Base class for entities with identity:

```php
use Modules\Core\Domain\Entity;

class Customer extends Entity
{
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
        ];
    }
}

$customer = new Customer();
$customer->getId();           // Get ID
$customer->hasId();           // Check if has ID
$customer->equals($other);    // Compare entities
```

### AggregateRoot

Aggregate root with domain event management:

```php
use Modules\Core\Domain\AggregateRoot;
use Modules\Core\Events\DomainEvent;

class Order extends AggregateRoot
{
    public function confirm(): void
    {
        // Business logic
        $this->status = 'confirmed';
        
        // Raise domain event
        $this->raise(new OrderConfirmed($this));
    }
    
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
        ];
    }
}

// In service layer
$order->confirm();

// Pull and dispatch events
foreach ($order->pullDomainEvents() as $event) {
    event($event);
}
```

### ValueObject

Abstract value object contract:

```php
use Modules\Core\Domain\ValueObject;

class CustomerId extends ValueObject
{
    public function __construct(private readonly string $value) {}
    
    public function equals(ValueObject $other): bool
    {
        return $other instanceof static && $this->value === $other->value;
    }
    
    public function toArray(): array
    {
        return ['value' => $this->value];
    }
    
    public function __toString(): string
    {
        return $this->value;
    }
}
```

## API Response Helpers

### ApiResponse

Standardized JSON responses:

```php
use Modules\Core\Support\ApiResponse;

// Success response
return ApiResponse::success($data, 'Operation successful', 200);

// Error response
return ApiResponse::error('Something went wrong', 400, $errors);

// Specific status responses
return ApiResponse::created($data, 'Resource created');
return ApiResponse::noContent();
return ApiResponse::notFound('Resource not found');
return ApiResponse::unauthorized('Unauthorized');
return ApiResponse::forbidden('Forbidden');
return ApiResponse::serverError('Internal error');

// Validation error
return ApiResponse::validationError([
    'email' => ['Email is required']
], 'Validation failed');

// Paginated response
return ApiResponse::paginated($collection, 'Success');

// Response with meta data
return ApiResponse::withMeta($data, ['total' => 100], 'Success');
```

### BaseApiController

Base controller with response methods:

```php
use Modules\Core\Http\Controllers\BaseApiController;

class CustomerController extends BaseApiController
{
    public function store(CreateCustomerRequest $request)
    {
        $customer = $this->customerService->create($request->validated());
        return $this->created($customer, 'Customer created successfully');
    }
    
    public function destroy(string $id)
    {
        $this->customerService->delete($id);
        return $this->noContent();
    }
    
    public function show(string $id)
    {
        $customer = $this->customerService->findById($id);
        
        if (!$customer) {
            return $this->notFound('Customer not found');
        }
        
        return $this->success($customer);
    }
}
```

## Event Infrastructure

### DomainEvent

Base domain event with timestamp:

```php
use Modules\Core\Events\DomainEvent;

class OrderConfirmed extends DomainEvent
{
    public function __construct(
        public readonly Order $order
    ) {
        parent::__construct();
    }
    
    public function toArray(): array
    {
        return [
            'order_id' => $this->order->id,
            'occurred_at' => $this->occurredAt()->format('Y-m-d H:i:s'),
        ];
    }
}

// In aggregate root
$this->raise(new OrderConfirmed($this));

// In service layer
foreach ($order->pullDomainEvents() as $event) {
    event($event); // Dispatch via Laravel
}
```

## Best Practices

### 1. Use Value Objects for Domain Concepts

```php
// ❌ Bad - primitive obsession
$customer->email = 'john@example.com';

// ✅ Good - value object
$customer->email = Email::fromString('john@example.com');
```

### 2. Use Custom Exceptions for Domain Errors

```php
// ❌ Bad - generic exception
throw new Exception('Customer not found');

// ✅ Good - domain exception
throw NotFoundException::entity(Customer::class, $id);
```

### 3. Use Domain Events for Side Effects

```php
// ❌ Bad - tightly coupled
public function confirmOrder(Order $order)
{
    $order->status = 'confirmed';
    $this->emailService->sendConfirmation($order);
    $this->inventoryService->reserve($order);
}

// ✅ Good - event-driven
public function confirmOrder(Order $order)
{
    $order->confirm(); // Raises OrderConfirmed event
    
    foreach ($order->pullDomainEvents() as $event) {
        event($event); // Listeners handle email, inventory
    }
}
```

### 4. Use Standardized API Responses

```php
// ❌ Bad - inconsistent
return response()->json(['data' => $customer]);

// ✅ Good - standardized
return ApiResponse::success($customer, 'Customer retrieved');
```

## Testing

### Testing Value Objects

```php
use Tests\TestCase;
use Modules\Core\ValueObjects\Email;
use Modules\Core\Exceptions\ValidationException;

class EmailTest extends TestCase
{
    public function test_it_validates_email_format()
    {
        $this->expectException(ValidationException::class);
        Email::fromString('invalid-email');
    }
    
    public function test_it_creates_valid_email()
    {
        $email = Email::fromString('john@example.com');
        $this->assertEquals('john@example.com', $email->getValue());
    }
    
    public function test_it_extracts_domain()
    {
        $email = Email::fromString('john@example.com');
        $this->assertEquals('example.com', $email->getDomain());
    }
}
```

### Testing Exceptions

```php
use Tests\TestCase;
use Modules\Core\Exceptions\NotFoundException;

class NotFoundExceptionTest extends TestCase
{
    public function test_it_creates_entity_not_found_exception()
    {
        $exception = NotFoundException::entity(Customer::class, '123');
        
        $this->assertEquals('Customer not found with identifier: 123', $exception->getMessage());
        $this->assertEquals(404, $exception->getCode());
    }
}
```

## Performance Considerations

1. **Value Objects are Immutable**: Create new instances for modifications
2. **BCMath for Money**: Precise calculations, slight performance overhead
3. **Domain Events**: Batch dispatch events to reduce overhead
4. **Caching**: Value objects can be cached safely (immutable)

## Security Considerations

1. **Validation**: All value objects validate input
2. **Immutability**: Prevents accidental modifications
3. **Type Safety**: PHP 8.2 strict types enforced
4. **Exception Handling**: Never expose internal details in exceptions

## Dependencies

**Zero third-party packages** - All implementations use:
- Native PHP 8.2+ features (readonly, BCMath, GD)
- Native Laravel features (Eloquent, Events, Exceptions)
- PSR-12 coding standards

## License

MIT
