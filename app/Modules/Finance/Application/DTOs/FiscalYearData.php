<?php

declare(strict_types=1);

namespace Modules\Finance\Application\DTOs;

class FiscalYearData
{
    public function __construct(
        public readonly int $tenant_id,
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
            name: (string) $data['name'],
            start_date: (string) $data['start_date'],
            end_date: (string) $data['end_date'],
            status: (string) ($data['status'] ?? 'open'),
            id: isset($data['id']) ? (int) $data['id'] : null,
        );
    }
}
