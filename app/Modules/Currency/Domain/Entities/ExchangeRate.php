<?php
declare(strict_types=1);
namespace Modules\Currency\Domain\Entities;

/**
 * Exchange rate between two currencies for a specific tenant.
 * Rates are directional: from → to at the given rate.
 * validFrom/validTo allow time-bounded rates (null = no bound).
 */
class ExchangeRate
{
    public function __construct(
        private ?int $id,
        private int $tenantId,
        private string $fromCurrency,
        private string $toCurrency,
        private float $rate,
        private string $source,       // manual|api|bank
        private ?\DateTimeInterface $validFrom,
        private ?\DateTimeInterface $validTo,
        private ?\DateTimeInterface $createdAt,
        private ?\DateTimeInterface $updatedAt,
    ) {}

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getFromCurrency(): string { return $this->fromCurrency; }
    public function getToCurrency(): string { return $this->toCurrency; }
    public function getRate(): float { return $this->rate; }
    public function getSource(): string { return $this->source; }
    public function getValidFrom(): ?\DateTimeInterface { return $this->validFrom; }
    public function getValidTo(): ?\DateTimeInterface { return $this->validTo; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }

    public function isValidAt(\DateTimeInterface $date): bool
    {
        if ($this->validFrom !== null && $date < $this->validFrom) {
            return false;
        }
        if ($this->validTo !== null && $date > $this->validTo) {
            return false;
        }
        return true;
    }

    public function convert(float $amount): float
    {
        return $amount * $this->rate;
    }

    public function invertedRate(): float
    {
        if (abs($this->rate) < PHP_FLOAT_EPSILON) {
            throw new \DomainException("Cannot invert a zero exchange rate.");
        }
        return 1.0 / $this->rate;
    }
}
