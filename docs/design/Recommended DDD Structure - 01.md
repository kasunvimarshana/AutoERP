## Recommended DDD Structure

```txt
app/
├── Shared/
│   ├── Domain/
│   │   ├── ValueObjects/
│   │   ├── Exceptions/
│   │   └── Traits/
│   │
│   ├── Application/
│   │   └── Services/
│   │
│   └── Infrastructure/
│       └── Persistence/
│
├── Domains/
│   ├── User/
│   │   ├── Domain/
│   │   │   ├── Entities/
│   │   │   ├── ValueObjects/
│   │   │   ├── Repositories/
│   │   │   ├── Services/
│   │   │   ├── Events/
│   │   │   └── Exceptions/
│   │   │
│   │   ├── Application/
│   │   │   ├── UseCases/
│   │   │   ├── DTOs/
│   │   │   └── Actions/
│   │   │
│   │   ├── Infrastructure/
│   │   │   ├── Persistence/
│   │   │   │   ├── Models/
│   │   │   │   ├── Repositories/
│   │   │   │   └── Migrations/
│   │   │   │
│   │   │   ├── Providers/
│   │   │   └── Services/
│   │   │
│   │   └── Presentation/
│   │       ├── Controllers/
│   │       ├── Requests/
│   │       ├── Resources/
│   │       └── Routes/
│   │
│   ├── Product/
│   ├── Order/
│   └── Payment/
│
├── Http/
├── Console/
└── Providers/
```

---

# Layer Explain

## 1. Domain Layer

Business rules only.

```txt
Domain/
├── Entities/
├── ValueObjects/
├── Repositories/
├── Services/
```

Example:

```php
// Domain/Repositories/UserRepository.php

interface UserRepository
{
    public function findByEmail(string $email);
}
```

---

## 2. Application Layer

Use cases / app logic.

```txt
Application/
├── UseCases/
├── DTOs/
└── Actions/
```

Example:

```php
class RegisterUserUseCase
{
    public function execute(RegisterUserDTO $dto)
    {
        //
    }
}
```

---

## 3. Infrastructure Layer

Database / external services.

```txt
Infrastructure/
├── Persistence/
│   ├── Models/
│   └── Repositories/
```

Example:

```php
class EloquentUserRepository implements UserRepository
{
    //
}
```

---

## 4. Presentation Layer

API / Controllers / Requests

```txt
Presentation/
├── Controllers/
├── Requests/
└── Resources/
```

---

# Simple Real Example

## User Domain

```txt
Domains/User/
├── Domain/
├── Application/
├── Infrastructure/
└── Presentation/
```

---

# Example Flow

```txt
Controller
   ↓
UseCase
   ↓
Repository Interface
   ↓
Eloquent Repository
   ↓
Database
```

---

# Best Practices

✅ Don't put Laravel dependencies in the domain layer
✅ Don't put business logic in Controllers
✅ Repository interfaces in the domain layer
✅ Eloquent implementation in the infrastructure laye
✅ UseCases = single responsibility

---

# Small Project

To avoid over-engineering, use this simplified version:

```txt
app/
├── Domains/
│   ├── User/
│   │   ├── Models/
│   │   ├── Actions/
│   │   ├── Services/
│   │   ├── Controllers/
│   │   └── Requests/
```
