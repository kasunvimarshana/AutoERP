<?php
declare(strict_types=1);
namespace Modules\Contract\Domain\Entities;

/** An individual deliverable or milestone line within a contract. */
class ContractLine
{
    public function __construct(
        private ?int $id,
        private int $contractId,
        private string $description,
        private ?int $productId,
        private float $quantity,
        private float $unitPrice,
        private float $totalPrice,
        private ?\DateTimeInterface $dueDate,
        private bool $isDelivered,
        private ?\DateTimeInterface $deliveredAt,
        private ?\DateTimeInterface $createdAt,
    ) {}

    public function getId(): ?int { return $this->id; }
    public function getContractId(): int { return $this->contractId; }
    public function getDescription(): string { return $this->description; }
    public function getProductId(): ?int { return $this->productId; }
    public function getQuantity(): float { return $this->quantity; }
    public function getUnitPrice(): float { return $this->unitPrice; }
    public function getTotalPrice(): float { return $this->totalPrice; }
    public function getDueDate(): ?\DateTimeInterface { return $this->dueDate; }
    public function isDelivered(): bool { return $this->isDelivered; }
    public function getDeliveredAt(): ?\DateTimeInterface { return $this->deliveredAt; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }

    public function markDelivered(): void
    {
        if ($this->isDelivered) {
            throw new \DomainException("Contract line is already delivered.");
        }
        $this->isDelivered  = true;
        $this->deliveredAt  = new \DateTimeImmutable();
    }
}
