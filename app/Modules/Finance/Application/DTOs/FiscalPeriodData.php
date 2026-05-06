<?php

declare(strict_types=1);

namespace Modules\Finance\Application\DTOs;

class FiscalPeriodData
{
    public function __construct(
        public readonly int $tenant_id,
        public readonly int $fiscal_year_id,
        public readonly int $period_number,
        public readonly string $name,
        public readonly string $start_date,
        public readonly string $end_date,
        public readonly string $status = 'open',
        public readonly ?int $id = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            tenant_id: (int) $data['tenant_id'],
            fiscal_year_id: (int) $data['fiscal_year_id'],
            period_number: (int) $data['period_number'],
            name: (string) $data['name'],
            start_date: (string) $data['start_date'],
            end_date: (string) $data['end_date'],
            status: (string) ($data['status'] ?? 'open'),
            id: isset($data['id']) ? (int) $data['id'] : null,
        );
    }
}
