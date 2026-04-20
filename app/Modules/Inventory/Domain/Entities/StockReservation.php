<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Entities;

class StockReservation
{
    public function __construct(
        private readonly int $tenantId,
        private readonly int $productId,
        private readonly ?int $variantId,
        private readonly ?int $batchId,
        private readonly ?int $serialId,
        private readonly int $locationId,
        private readonly string $quantity,
        private readonly ?string $reservedForType,
        private readonly ?int $reservedForId,
        private readonly ?string $expiresAt,
        private ?int $id = null,
    ) {}

    public function getId(): ?int { return $this->id; }

    public function getTenantId(): int { return $this->tenantId; }

    public function getProductId(): int { return $this->productId; }

    public function getVariantId(): ?int { return $this->variantId; }

    public function getBatchId(): ?int { return $this->batchId; }

    public function getSerialId(): ?int { return $this->serialId; }

    public function getLocationId(): int { return $this->locationId; }

    public function getQuantity(): string { return $this->quantity; }

    public function getReservedForType(): ?string { return $this->reservedForType; }

    public function getReservedForId(): ?int { return $this->reservedForId; }

    public function getExpiresAt(): ?string { return $this->expiresAt; }
}
