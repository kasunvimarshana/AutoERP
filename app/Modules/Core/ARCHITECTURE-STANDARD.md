# Core Architecture Standard

This document defines the simplified Core baseline after removing over-engineering.

## 1) Comparison Summary

### Previous Core

- Mixed service/repository abstractions with framework leakage.
- Repository contracts exposed ORM classes.
- Shared kernel and infrastructure concerns were fragmented.

### Current refactored Core before simplification

- Correctly removed ORM leakage from contracts.
- Introduced many generic layers (CQRS/pattern/context/audit abstractions) not yet used by modules.
- Added DTO and entity-related structures, but some abstractions were speculative.

### Simplified result

- Keep only abstractions used by the active architecture baseline.
- DTO usage: repository boundary uses `DataRecord` and `PagedResult`.
- Entity usage: `Entity`, `AggregateRoot`, and value objects remain domain-side only.
- Remove unused CQRS/pattern/context/audit abstraction layers.

## 2) Final Simplified Core Structure

```text
app/Modules/Core/
  Application/
    Configuration/
      CoreConfigKey.php
    Contracts/
      ClockInterface.php
      FileStorageServiceInterface.php
      SlugGeneratorInterface.php
      UuidGeneratorInterface.php
    DTO/
      DataRecord.php
      PagedResult.php
      PaginationRequest.php
    Pipelines/
      Pipeline.php
      PipelineStageInterface.php
    Repositories/
      Contracts/
        RepositoryPortInterface.php
    Results/
      Error.php
      Result.php
  Domain/
    Entities/
      AggregateRoot.php
      Entity.php
    Events/
      AbstractDomainEvent.php
      DomainEventInterface.php
      RecordsDomainEvents.php
    Exceptions/
      CoreException.php
      DomainException.php
      InvalidValueObjectException.php
    Specifications/
      AndSpecification.php
      NotSpecification.php
      OrSpecification.php
      SpecificationInterface.php
    ValueObjects/
      OrganizationUnitId.php
      TenantId.php
      Uuid.php
      ValueObject.php
  Infrastructure/
    Config/
      core.php
    Persistence/
      Eloquent/
        Concerns/
        Constants/
          SchemaColumns.php
        Models/
          CoreModel.php
        Repositories/
          EloquentRepository.php
    Providers/
      CoreServiceProvider.php
    Services/
      FileStorageService.php
      SlugGenerator.php
    Support/
      LaravelUuidGenerator.php
      SystemClock.php
```

## 3) DTO vs Entity Rules

- Use DTOs (`DataRecord`, `PagedResult`, `PaginationRequest`) for application boundary transport.
- Use Entities/ValueObjects only for domain behavior and identity/value semantics.
- Never expose Eloquent models from repository/application boundaries.
- Do not use entities as generic transport containers.

## 4) Dependency Flow

```mermaid
flowchart LR
  Domain --> Application
  Application --> Infrastructure
  Infrastructure -.implements.-> Application
```

## 5) Simplification Principles Applied

- Removed unused abstraction layers (CQRS interfaces, generic pattern interfaces, unused cross-cutting contracts).
- Collapsed mutation repository API to id-based operations to avoid DTO/entity overlap.
- Reduced configuration surface to active keys only.
- Kept explicit constructor dependency injection and framework isolation in infrastructure.

## 6) Module Guidelines

- Depend on Core contracts and DTO boundaries, not infrastructure classes.
- Keep module-specific policies/strategies inside modules unless reused broadly.
- Perform calculations in application/domain, not in database-generated totals.
- Prefer explicit simple contracts over speculative generic abstractions.
