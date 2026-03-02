<?php

declare(strict_types=1);

namespace Modules\Core\Domain\Entities;

use Modules\Core\Domain\ValueObjects\TenantId;
use DateTimeImmutable;

class Organisation
{
    public function __construct(
        private readonly int $id,
        private readonly TenantId $tenantId,
        private string $name,
        private string $currency,
        private string $timezone,
        private ?DateTimeImmutable $fiscalYearStart,
    ) {}

    public function getId(): int
    {
        return $this->id;
    }

    public function getTenantId(): TenantId
    {
        return $this->tenantId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getTimezone(): string
    {
        return $this->timezone;
    }

    public function getFiscalYearStart(): ?DateTimeImmutable
    {
        return $this->fiscalYearStart;
    }

    public function rename(string $name): void
    {
        if (empty(trim($name))) {
            throw new \InvalidArgumentException('Organisation name cannot be empty.');
        }

        $this->name = $name;
    }

    public function changeCurrency(string $currency): void
    {
        if (strlen($currency) !== 3) {
            throw new \InvalidArgumentException('Currency code must be exactly 3 characters (ISO 4217).');
        }

        $this->currency = strtoupper($currency);
    }
}
