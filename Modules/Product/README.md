# Product Module

Manages the product catalogue including variants, categories, brands and units of measure.

## Responsibilities

- Product CRUD (single, variable, combo, service types)
- Product variants with attribute combinations
- Category hierarchy (self-referential)
- Brand management
- Unit of measure management
- SKU uniqueness enforcement per tenant
- BCMath-based margin calculation

## Architecture

```
Product/
├── Domain/
│   ├── ValueObjects/SKU.php           — Validated SKU VO
│   ├── ValueObjects/Price.php         — BCMath price VO
│   ├── Enums/ProductType.php
│   ├── Entities/Product.php           — calculateMargin() uses BCMath
│   ├── Entities/ProductVariant.php
│   ├── Entities/Category.php
│   ├── Entities/Brand.php
│   ├── Entities/Unit.php
│   └── Contracts/ProductRepositoryInterface.php
├── Application/
│   ├── Commands/CreateProductCommand.php
│   ├── Commands/UpdateProductCommand.php
│   ├── Handlers/CreateProductHandler.php
│   ├── Queries/GetProductQuery.php
│   └── DTOs/ProductDTO.php
├── Infrastructure/
│   ├── Models/                        — All with GlobalScope for tenant isolation
│   ├── Repositories/ProductRepository.php
│   └── Database/Migrations/
├── Interfaces/
│   └── Http/
│       ├── Controllers/ProductController.php
│       ├── Requests/CreateProductRequest.php
│       └── Resources/ProductResource.php
└── Providers/ProductServiceProvider.php
```

## API Endpoints

| Method | Endpoint               | Description            |
|--------|------------------------|------------------------|
| GET    | /api/v1/products       | List products (paged)  |
| POST   | /api/v1/products       | Create product         |
| GET    | /api/v1/products/{id}  | Get product by ID      |
| PUT    | /api/v1/products/{id}  | Update product         |
| DELETE | /api/v1/products/{id}  | Soft-delete product    |

## Multi-Tenancy

All models use a `GlobalScope` keyed on `tenant.id` from the IoC container.
SKU uniqueness is enforced per tenant via `[tenant_id, sku]` unique index.
