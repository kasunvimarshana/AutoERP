<?php
declare(strict_types=1);
namespace Modules\Currency\Domain\Entities;

class Currency
{
    public function __construct(
        private ?int $id,
        private string $code,        // ISO 4217, e.g. USD, EUR
        private string $name,
        private string $symbol,
        private int $decimalPlaces,
        private bool $isBase,        // true = platform base currency
        private bool $isActive,
        private ?\DateTimeInterface $createdAt,
        private ?\DateTimeInterface $updatedAt,
    ) {}

    public function getId(): ?int { return $this->id; }
    public function getCode(): string { return $this->code; }
    public function getName(): string { return $this->name; }
    public function getSymbol(): string { return $this->symbol; }
    public function getDecimalPlaces(): int { return $this->decimalPlaces; }
    public function isBase(): bool { return $this->isBase; }
    public function isActive(): bool { return $this->isActive; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }

    public function activate(): void { $this->isActive = true; }
    public function deactivate(): void { $this->isActive = false; }

    public function format(float $amount): string
    {
        return $this->symbol . number_format($amount, $this->decimalPlaces);
    }
}
