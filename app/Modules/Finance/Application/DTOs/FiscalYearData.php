<?php

declare(strict_types=1);

namespace Modules\Finance\Application\DTOs;

class FiscalYearData
{
    public function __construct(
        public readonly int $tenantId,
        public readonly string $name,
        public readonly string $startDate,
        public readonly string $endDate,
        public readonly string $status = 'open',
        public readonly int $rowVersion = 1,
        public readonly ?int $id = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            tenantId: (int) $data['tenant_id'],
            name: (string) $data['name'],
            startDate: (string) $data['start_date'],
            endDate: (string) $data['end_date'],
            status: (string) ($data['status'] ?? 'open'),
            rowVersion: (int) ($data['row_version'] ?? 1),
            id: isset($data['id']) ? (int) $data['id'] : null,
        );
    }
}
