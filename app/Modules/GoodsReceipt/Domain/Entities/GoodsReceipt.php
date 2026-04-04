<?php
namespace Modules\GoodsReceipt\Domain\Entities;
use Modules\Core\Domain\Entities\BaseEntity;
use Modules\GoodsReceipt\Domain\ValueObjects\GoodsReceiptStatus;

class GoodsReceipt extends BaseEntity
{
    public function __construct(
        ?int $id,
        public readonly int $tenantId,
        public readonly int $warehouseId,
        public readonly string $grNumber,
        public string $status,
        public readonly ?int $purchaseOrderId = null,
        public readonly ?int $supplierId = null,
        public readonly ?string $supplierReference = null,
        public readonly ?string $notes = null,
        public ?\DateTimeImmutable $receivedAt = null,
        public ?int $receivedBy = null,
        public ?int $inspectedBy = null,
        public ?\DateTimeImmutable $inspectedAt = null,
        public ?int $putAwayBy = null,
        public ?\DateTimeImmutable $putAwayAt = null,
    ) {
        parent::__construct($id);
    }

    public function inspect(int $inspectedBy): void
    {
        if (!in_array($this->status, [GoodsReceiptStatus::PENDING, GoodsReceiptStatus::UNDER_INSPECTION], true)) {
            throw new \DomainException("Cannot inspect GR with status: {$this->status}");
        }
        $this->status = GoodsReceiptStatus::INSPECTED;
        $this->inspectedBy = $inspectedBy;
        $this->inspectedAt = new \DateTimeImmutable();
    }

    public function putAway(int $putAwayBy): void
    {
        if ($this->status !== GoodsReceiptStatus::INSPECTED) {
            throw new \DomainException("Cannot put away GR with status: {$this->status}");
        }
        $this->status = GoodsReceiptStatus::PUT_AWAY;
        $this->putAwayBy = $putAwayBy;
        $this->putAwayAt = new \DateTimeImmutable();
    }

    public function complete(): void
    {
        if ($this->status !== GoodsReceiptStatus::PUT_AWAY) {
            throw new \DomainException("Cannot complete GR with status: {$this->status}");
        }
        $this->status = GoodsReceiptStatus::COMPLETED;
    }
}
