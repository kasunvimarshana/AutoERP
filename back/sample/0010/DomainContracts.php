<?php

declare(strict_types=1);

// ═══════════════════════════════════════════════════════════════════
// WAREHOUSE MODULE — Domain
// ═══════════════════════════════════════════════════════════════════

namespace Modules\Warehouse\Domain\Entities;

final class Warehouse
{
    public function __construct(
        private readonly int    $tenantId,
        private string          $name,
        private string          $code,
        private string          $type           = 'standard',
        private bool            $isActive       = true,
        private bool            $allowsNegativeStock = false,
        private ?string         $valuationMethod = null,
        private ?string         $stockRotation  = null,
        private ?string         $allocationAlgorithm = null,
        private ?array          $address        = null,
        private ?array          $contact        = null,
        private ?array          $attributes     = null,
        private int             $sortOrder      = 0,
        private ?int            $organizationId = null,
        private ?int            $id             = null,
    ) {}

    public function getId(): ?int              { return $this->id; }
    public function getTenantId(): int         { return $this->tenantId; }
    public function getName(): string          { return $this->name; }
    public function getCode(): string          { return $this->code; }
    public function getType(): string          { return $this->type; }
    public function isActive(): bool           { return $this->isActive; }
    public function allowsNegativeStock(): bool { return $this->allowsNegativeStock; }
    public function getValuationMethod(): ?string { return $this->valuationMethod; }
    public function getStockRotation(): ?string   { return $this->stockRotation; }
    public function getAllocationAlgorithm(): ?string { return $this->allocationAlgorithm; }
    public function getAddress(): ?array       { return $this->address; }

    public function updateDetails(string $name, ?string $valuationMethod, ?string $stockRotation, ?string $allocationAlgorithm): void
    {
        $this->name               = $name;
        $this->valuationMethod    = $valuationMethod;
        $this->stockRotation      = $stockRotation;
        $this->allocationAlgorithm = $allocationAlgorithm;
    }

    public function activate(): void    { $this->isActive = true; }
    public function deactivate(): void  { $this->isActive = false; }
    public function assignId(int $id): void { if ($this->id !== null) throw new \LogicException('ID already set'); $this->id = $id; }
}

namespace Modules\Warehouse\Domain\RepositoryInterfaces;

use Modules\Warehouse\Domain\Entities\Warehouse;
use Modules\Core\Domain\RepositoryInterfaces\BaseRepositoryInterface;

interface WarehouseRepositoryInterface extends BaseRepositoryInterface
{
    public function findById(int $id): ?Warehouse;
    public function findByCode(string $code, int $tenantId): ?Warehouse;
    public function findByTenant(int $tenantId, array $filters = []): array;
    public function save(mixed $entity): Warehouse;
}


// ═══════════════════════════════════════════════════════════════════
// PROCUREMENT MODULE — Domain Entities
// ═══════════════════════════════════════════════════════════════════

namespace Modules\Procurement\Domain\ValueObjects;

use InvalidArgumentException;

final class PurchaseOrderStatus
{
    public const DRAFT              = 'draft';
    public const PENDING_APPROVAL   = 'pending_approval';
    public const APPROVED           = 'approved';
    public const SENT               = 'sent';
    public const PARTIALLY_RECEIVED = 'partially_received';
    public const RECEIVED           = 'received';
    public const CANCELLED          = 'cancelled';
    public const CLOSED             = 'closed';

    public const VALID = [
        self::DRAFT, self::PENDING_APPROVAL, self::APPROVED, self::SENT,
        self::PARTIALLY_RECEIVED, self::RECEIVED, self::CANCELLED, self::CLOSED,
    ];
    public const OPEN_STATUSES = [self::APPROVED, self::SENT, self::PARTIALLY_RECEIVED];
    public const RECEIVABLE    = [self::APPROVED, self::SENT, self::PARTIALLY_RECEIVED];

    public function __construct(private readonly string $value)
    {
        if (!in_array($value, self::VALID, true)) throw new InvalidArgumentException("Invalid PO status: {$value}");
    }

    public function value(): string     { return $this->value; }
    public function isDraft(): bool     { return $this->value === self::DRAFT; }
    public function isApproved(): bool  { return $this->value === self::APPROVED; }
    public function isReceivable(): bool { return in_array($this->value, self::RECEIVABLE, true); }
    public function isClosed(): bool    { return in_array($this->value, [self::RECEIVED, self::CANCELLED, self::CLOSED], true); }
    public function __toString(): string { return $this->value; }
}

namespace Modules\Procurement\Domain\Exceptions;

final class PurchaseOrderNotFoundException extends \RuntimeException
{
    public function __construct(int $id) { parent::__construct("Purchase order not found: #{$id}"); }
}
final class PurchaseOrderNotReceivableException extends \RuntimeException
{
    public function __construct(string $status) { parent::__construct("PO cannot be received in status: {$status}"); }
}

namespace Modules\Procurement\Domain\RepositoryInterfaces;

use Modules\Core\Domain\RepositoryInterfaces\BaseRepositoryInterface;

interface PurchaseOrderRepositoryInterface extends BaseRepositoryInterface
{
    public function findByNumber(string $poNumber, int $tenantId): mixed;
    public function findOpenBySupplier(int $supplierId, int $tenantId): array;
    public function findOverdue(int $tenantId): array;
    public function save(mixed $entity): mixed;
}

interface SupplierRepositoryInterface extends BaseRepositoryInterface
{
    public function findByCode(string $code, int $tenantId): mixed;
    public function findPreferredForProduct(int $productId, int $tenantId): mixed;
    public function save(mixed $entity): mixed;
}


// ═══════════════════════════════════════════════════════════════════
// SALES MODULE — Domain Value Objects, Exceptions, Repository Interfaces
// ═══════════════════════════════════════════════════════════════════

namespace Modules\Sales\Domain\ValueObjects;

use InvalidArgumentException;

final class SalesOrderStatus
{
    public const DRAFT             = 'draft';
    public const CONFIRMED         = 'confirmed';
    public const PICKING           = 'picking';
    public const PARTIALLY_PICKED  = 'partially_picked';
    public const PICKED            = 'picked';
    public const PACKING           = 'packing';
    public const PACKED            = 'packed';
    public const SHIPPED           = 'shipped';
    public const DELIVERED         = 'delivered';
    public const CANCELLED         = 'cancelled';
    public const ON_HOLD           = 'on_hold';

    public const VALID = [
        self::DRAFT, self::CONFIRMED, self::PICKING, self::PARTIALLY_PICKED,
        self::PICKED, self::PACKING, self::PACKED, self::SHIPPED, self::DELIVERED,
        self::CANCELLED, self::ON_HOLD,
    ];
    public const CANCELLABLE = [self::DRAFT, self::CONFIRMED, self::ON_HOLD];
    public const FULFILLABLE = [self::CONFIRMED, self::PICKING, self::PARTIALLY_PICKED];

    public function __construct(private readonly string $value)
    {
        if (!in_array($value, self::VALID, true)) throw new InvalidArgumentException("Invalid SO status: {$value}");
    }

    public function value(): string      { return $this->value; }
    public function isDraft(): bool      { return $this->value === self::DRAFT; }
    public function isConfirmed(): bool  { return $this->value === self::CONFIRMED; }
    public function isCancellable(): bool { return in_array($this->value, self::CANCELLABLE, true); }
    public function isFulfillable(): bool { return in_array($this->value, self::FULFILLABLE, true); }
    public function isDelivered(): bool  { return $this->value === self::DELIVERED; }
    public function __toString(): string { return $this->value; }
}

final class FulfillmentStatus
{
    public const UNFULFILLED         = 'unfulfilled';
    public const PARTIALLY_FULFILLED = 'partially_fulfilled';
    public const FULFILLED           = 'fulfilled';
    public const RETURNED            = 'returned';

    public const VALID = [self::UNFULFILLED, self::PARTIALLY_FULFILLED, self::FULFILLED, self::RETURNED];

    public function __construct(private readonly string $value)
    {
        if (!in_array($value, self::VALID, true)) throw new InvalidArgumentException("Invalid fulfillment status: {$value}");
    }
    public function value(): string { return $this->value; }
    public function isFulfilled(): bool { return $this->value === self::FULFILLED; }
    public function isPartial(): bool { return $this->value === self::PARTIALLY_FULFILLED; }
    public function __toString(): string { return $this->value; }
}

namespace Modules\Sales\Domain\Exceptions;

final class SalesOrderNotFoundException extends \RuntimeException
{
    public function __construct(int|string $id) { parent::__construct("Sales order not found: {$id}"); }
}
final class InsufficientStockException extends \RuntimeException
{
    public function __construct(int $productId, float $requested, float $available)
    { parent::__construct("Product #{$productId}: requested {$requested}, available {$available}"); }
}
final class SalesOrderNotCancellableException extends \RuntimeException
{
    public function __construct(string $status) { parent::__construct("Cannot cancel order in status: {$status}"); }
}

namespace Modules\Sales\Domain\RepositoryInterfaces;

use Modules\Core\Domain\RepositoryInterfaces\BaseRepositoryInterface;

interface SalesOrderRepositoryInterface extends BaseRepositoryInterface
{
    public function findByNumber(string $orderNumber, int $tenantId): mixed;
    public function findUnfulfilled(int $tenantId, ?int $warehouseId): array;
    public function findByCustomer(int $customerId, int $tenantId): mixed;
    public function save(mixed $entity): mixed;
}

interface CustomerRepositoryInterface extends BaseRepositoryInterface
{
    public function findByCode(string $code, int $tenantId): mixed;
    public function save(mixed $entity): mixed;
}
