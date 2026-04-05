<?php
declare(strict_types=1);
namespace Modules\GS1\Domain\Entities;
class Gs1Label {
    public function __construct(
        private ?int $id,
        private int $tenantId,
        private int $productId,
        private string $gs1Type,      // gtin-8|gtin-12|gtin-13|gtin-14|sscc|gln|grai|giai
        private string $gs1Value,
        private ?string $batchNumber,
        private ?string $lotNumber,
        private ?string $serialNumber,
        private ?string $expiryDate,
        private ?float $netWeight,
        private ?string $countryOfOrigin,
        private ?\DateTimeInterface $createdAt,
        private ?\DateTimeInterface $updatedAt,
    ) {}
    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getProductId(): int { return $this->productId; }
    public function getGs1Type(): string { return $this->gs1Type; }
    public function getGs1Value(): string { return $this->gs1Value; }
    public function getBatchNumber(): ?string { return $this->batchNumber; }
    public function getLotNumber(): ?string { return $this->lotNumber; }
    public function getSerialNumber(): ?string { return $this->serialNumber; }
    public function getExpiryDate(): ?string { return $this->expiryDate; }
    public function getNetWeight(): ?float { return $this->netWeight; }
    public function getCountryOfOrigin(): ?string { return $this->countryOfOrigin; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }
    public function buildBarcode(): string {
        // Build GS1-128 application identifier string
        $ai = '';
        if ($this->gs1Type === 'gtin-13') $ai .= "(01){$this->gs1Value}";
        if ($this->batchNumber) $ai .= "(10){$this->batchNumber}";
        if ($this->lotNumber) $ai .= "(23){$this->lotNumber}";
        if ($this->serialNumber) $ai .= "(21){$this->serialNumber}";
        if ($this->expiryDate) $ai .= "(17){$this->expiryDate}";
        return $ai;
    }
}
