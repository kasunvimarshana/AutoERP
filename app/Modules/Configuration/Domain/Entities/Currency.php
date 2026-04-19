<?php

declare(strict_types=1);

namespace Modules\Configuration\Domain\Entities;

class Currency
{
    public function __construct(
        private readonly string $code,
        private readonly string $name,
        private readonly ?string $symbol = null,
        private readonly int $decimalPlaces = 2,
        private readonly bool $isActive = true,
        private readonly ?int $id = null,
        private readonly ?\DateTimeInterface $createdAt = null,
        private readonly ?\DateTimeInterface $updatedAt = null,
    ) {}

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSymbol(): ?string
    {
        return $this->symbol;
    }

    public function getDecimalPlaces(): int
    {
        return $this->decimalPlaces;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }
}
