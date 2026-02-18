<?php

namespace App\Modules\Finance\DTOs;

class FinancialPeriodDTO
{
    public function __construct(
        public string $startDate,
        public string $endDate,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            startDate: $data['start_date'],
            endDate: $data['end_date'],
        );
    }

    public function toArray(): array
    {
        return [
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
        ];
    }
}
