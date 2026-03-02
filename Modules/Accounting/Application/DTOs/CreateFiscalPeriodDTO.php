<?php

declare(strict_types=1);

namespace Modules\Accounting\Application\DTOs;

/**
 * Data Transfer Object for creating a Fiscal Period.
 */
final class CreateFiscalPeriodDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $startDate,
        public readonly string $endDate,
        public readonly bool $isClosed,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name:      $data['name'],
            startDate: $data['start_date'],
            endDate:   $data['end_date'],
            isClosed:  (bool) ($data['is_closed'] ?? false),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name'       => $this->name,
            'start_date' => $this->startDate,
            'end_date'   => $this->endDate,
            'is_closed'  => $this->isClosed,
        ];
    }
}
