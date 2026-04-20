<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Entities;

class TransferOrder
{
    /**
     * @param  list<TransferOrderLine>  $lines
     */
    public function __construct(
        private readonly int $tenantId,
        private readonly int $fromWarehouseId,
        private readonly int $toWarehouseId,
        private readonly string $transferNumber,
        private readonly string $status,
        private readonly string $requestDate,
        private readonly ?string $expectedDate,
        private readonly ?string $shippedDate,
        private readonly ?string $receivedDate,
        private readonly ?string $notes,
        private readonly ?array $metadata,
        private readonly array $lines,
        private readonly ?int $orgUnitId = null,
        private ?int $id = null,
    ) {}

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTenantId(): int
    {
        return $this->tenantId;
    }

    public function getFromWarehouseId(): int
    {
        return $this->fromWarehouseId;
    }

    public function getToWarehouseId(): int
    {
        return $this->toWarehouseId;
    }

    public function getTransferNumber(): string
    {
        return $this->transferNumber;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getRequestDate(): string
    {
        return $this->requestDate;
    }

    public function getExpectedDate(): ?string
    {
        return $this->expectedDate;
    }

    public function getShippedDate(): ?string
    {
        return $this->shippedDate;
    }

    public function getReceivedDate(): ?string
    {
        return $this->receivedDate;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    /**
     * @return list<TransferOrderLine>
     */
    public function getLines(): array
    {
        return $this->lines;
    }

    public function getOrgUnitId(): ?int
    {
        return $this->orgUnitId;
    }
}
