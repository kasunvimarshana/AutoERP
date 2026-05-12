## Recommended DDD Structure

```txt
app/
│
├── Shared/
│   ├── Domain/
│   │   ├── ValueObjects/
│   │   ├── Exceptions/
│   │   ├── Traits/
│   │   └── Interfaces/
│   │
│   ├── Application/
│   │   └── Services/
│   │
│   ├── Infrastructure/
│   │   ├── Persistence/
│   │   ├── Services/
│   │   └── Providers/
│   │
│   └── Presentation/
│       └── Responses/
│
├── User/
│   ├── Domain/
│   │   ├── Entities/
│   │   │   └── User.php
│   │   │
│   │   ├── ValueObjects/
│   │   │   └── Email.php
│   │   │
│   │   ├── Repositories/
│   │   │   └── UserRepositoryInterface.php
│   │   │
│   │   ├── Services/
│   │   ├── Events/
│   │   ├── Exceptions/
│   │   └── Policies/
│   │
│   ├── Application/
│   │   ├── DTOs/
│   │   │   └── CreateUserDTO.php
│   │   │
│   │   ├── UseCases/
│   │   │   └── CreateUserUseCase.php
│   │   │
│   │   ├── Actions/
│   │   ├── Commands/
│   │   ├── Queries/
│   │   └── Mappers/
│   │
│   ├── Infrastructure/
│   │   ├── Persistence/
│   │   │   ├── Eloquent/
│   │   │   │   ├── Models/
│   │   │   │   │   └── UserModel.php
│   │   │   │   │
│   │   │   │   └── Repositories/
│   │   │   │       └── UserRepository.php
│   │   │   │
│   │   │   └── Migrations/
│   │   │
│   │   ├── Providers/
│   │   │   └── UserServiceProvider.php
│   │   │
│   │   └── ExternalServices/
│   │
│   └── Presentation/
│       ├── Controllers/
│       │   └── UserController.php
│       │
│       ├── Requests/
│       │   └── StoreUserRequest.php
│       │
│       ├── Resources/
│       └── Routes/
│           └── api.php
│
├── Product/
│   ├── Domain/
│   ├── Application/
│   ├── Infrastructure/
│   └── Presentation/
│
└── Order/
    ├── Domain/
    ├── Application/
    ├── Infrastructure/
    └── Presentation/
```

---

# Layer Responsibilities

## 1. Domain Layer

Business rules only.

Contains:

* Entities
* Value Objects
* Repository Interfaces
* Domain Services
* Domain Events

❌ NO:

* Laravel facades
* DB queries
* HTTP
* Controllers

Example:

```php
class Email
{
    public function __construct(
        private string $value
    ) {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException();
        }
    }

    public function value(): string
    {
        return $this->value;
    }
}
```

---

## 2. Application Layer

Use cases / business workflows.

Contains:

* UseCases
* Commands
* Queries
* DTOs
* Actions

Example:

```php
class CreateUserUseCase
{
    public function __construct(
        private UserRepositoryInterface $users
    ) {}

    public function execute(CreateUserDTO $dto): User
    {
        return $this->users->create($dto);
    }
}
```

---

## 3. Infrastructure Layer

Framework & external implementations.

Contains:

* Eloquent Models
* Repository implementations
* Queue
* Cache
* APIs
* Payment gateways

Example:

```php
class UserRepository implements UserRepositoryInterface
{
    public function create(CreateUserDTO $dto): User
    {
        return UserModel::create([
            'name' => $dto->name,
            'email' => $dto->email,
        ]);
    }
}
```

---

## 4. Presentation Layer

Everything HTTP/UI related.

Contains:

* Controllers
* Requests
* API Resources
* Routes

Example:

```php
class UserController extends Controller
{
    public function store(
        StoreUserRequest $request,
        CreateUserUseCase $useCase
    ) {
        $dto = new CreateUserDTO(...$request->validated());

        return $useCase->execute($dto);
    }
}
```

---

# Recommended Extra Conventions

## Use Actions for Small Tasks

```txt
Application/
└── Actions/
    └── SendWelcomeEmailAction.php
```

---

## CQRS Friendly Structure

```txt
Application/
├── Commands/
├── Queries/
└── Handlers/
```

---

## Repository Binding

```php
public function register()
{
    $this->app->bind(
        UserRepositoryInterface::class,
        UserRepository::class
    );
}
```

---

# My Recommended Practical Version (Best Balance)

```txt
app/
├── Domains/
│   ├── User/
│   ├── Product/
│   └── Order/
│
├── Shared/
└── Support/
```

Inside each domain:

```txt
User/
├── Domain/
├── Application/
├── Infrastructure/
└── Presentation/
```
