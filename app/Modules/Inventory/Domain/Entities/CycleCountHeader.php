<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Entities;

use Modules\Inventory\Domain\Entities\CycleCountLine;

class CycleCountHeader
{
    /**
     * @param  list<CycleCountLine>  $lines
     */
    public function __construct(
        private readonly int $tenantId,
        private readonly int $warehouseId,
        private readonly ?int $locationId,
        private readonly string $status,
        private readonly ?int $countedByUserId,
        private readonly ?string $countedAt,
        private readonly ?int $approvedByUserId,
        private readonly ?string $approvedAt,
        private readonly array $lines,
        private ?int $id = null,
    ) {}

    public function getId(): ?int { return $this->id; }

    public function getTenantId(): int { return $this->tenantId; }

    public function getWarehouseId(): int { return $this->warehouseId; }

    public function getLocationId(): ?int { return $this->locationId; }

    public function getStatus(): string { return $this->status; }

    public function getCountedByUserId(): ?int { return $this->countedByUserId; }

    public function getCountedAt(): ?string { return $this->countedAt; }

    public function getApprovedByUserId(): ?int { return $this->approvedByUserId; }

    public function getApprovedAt(): ?string { return $this->approvedAt; }

    /**
     * @return list<CycleCountLine>
     */
    public function getLines(): array { return $this->lines; }
}
