<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Events;

use Modules\Product\Domain\Entities\Product;

// ═══════════════════════════════════════════════════════════════════
// Domain Events — confirmed pattern from KVAutoERP PR #37
// ═══════════════════════════════════════════════════════════════════

final class ProductCreated
{
    public readonly \DateTimeImmutable $occurredAt;

    public function __construct(
        public readonly Product $product,
    ) {
        $this->occurredAt = new \DateTimeImmutable();
    }
}

final class ProductUpdated
{
    public readonly \DateTimeImmutable $occurredAt;

    public function __construct(
        public readonly Product $product,
        public readonly array   $changedFields,
    ) {
        $this->occurredAt = new \DateTimeImmutable();
    }
}

final class ProductStatusChanged
{
    public readonly \DateTimeImmutable $occurredAt;

    public function __construct(
        public readonly Product $product,
        public readonly string  $previousStatus,
        public readonly string  $newStatus,
    ) {
        $this->occurredAt = new \DateTimeImmutable();
    }
}

final class ProductDeleted
{
    public readonly \DateTimeImmutable $occurredAt;

    public function __construct(
        public readonly int $productId,
        public readonly int $tenantId,
    ) {
        $this->occurredAt = new \DateTimeImmutable();
    }
}


namespace Modules\Product\Domain\Exceptions;

// ═══════════════════════════════════════════════════════════════════
// Domain Exceptions — confirmed pattern from KVAutoERP PR #37
// ═══════════════════════════════════════════════════════════════════

final class ProductNotFoundException extends \RuntimeException
{
    public function __construct(int|string $identifier)
    {
        parent::__construct("Product not found: {$identifier}");
    }
}

final class ProductSkuAlreadyExistsException extends \RuntimeException
{
    public function __construct(string $sku)
    {
        parent::__construct("Product SKU already exists: {$sku}");
    }
}

final class ProductCannotBeSoldException extends \RuntimeException
{
    public function __construct(int $productId, string $reason)
    {
        parent::__construct("Product #{$productId} cannot be sold: {$reason}");
    }
}

final class InvalidProductTypeForOperationException extends \RuntimeException
{
    public function __construct(string $type, string $operation)
    {
        parent::__construct("Product type '{$type}' does not support operation: {$operation}");
    }
}


namespace Modules\Product\Domain\RepositoryInterfaces;

use Modules\Core\Domain\RepositoryInterfaces\BaseRepositoryInterface;
use Modules\Product\Domain\Entities\Product;

/**
 * ProductRepositoryInterface
 *
 * Lives in Domain layer — Infrastructure provides the Eloquent implementation.
 * Confirmed from KVAutoERP PR #37 imports:
 * use Modules\Product\Domain\RepositoryInterfaces\ProductRepositoryInterface;
 */
interface ProductRepositoryInterface extends BaseRepositoryInterface
{
    public function findById(int $id): ?Product;
    public function findBySku(string $sku, int $tenantId): ?Product;
    public function findByTenant(int $tenantId, array $filters = [], int $perPage = 25): mixed;
    public function save(mixed $entity): Product;
    public function skuExists(string $sku, int $tenantId, ?int $excludeId = null): bool;
    public function findLowStock(int $tenantId, ?int $warehouseId = null): array;
    public function findByType(string $type, int $tenantId): array;
    public function countByTenant(int $tenantId): int;
}
