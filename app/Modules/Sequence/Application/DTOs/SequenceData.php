<?php

declare(strict_types=1);

namespace Modules\Sequence\Application\DTOs;

final readonly class SequenceData
{
    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function __construct(
        public int $tenantId,
        public ?int $organizationUnitId,
        public string $documentType,
        public ?string $prefix = '',
        public ?string $suffix = '',
        public int $padding = 5,
        public int $nextNumber = 1,
        public string $periodType = 'yearly',
        public ?string $periodValue = null,
        public ?array $metadata = null,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            tenantId: (int) $data['tenant_id'],
            organizationUnitId: isset($data['organization_unit_id']) ? (int) $data['organization_unit_id'] : null,
            documentType: (string) $data['document_type'],
            prefix: isset($data['prefix']) ? (string) $data['prefix'] : '',
            suffix: isset($data['suffix']) ? (string) $data['suffix'] : '',
            padding: isset($data['padding']) ? (int) $data['padding'] : 5,
            nextNumber: isset($data['next_number']) ? (int) $data['next_number'] : 1,
            periodType: (string) ($data['period_type'] ?? 'yearly'),
            periodValue: $data['period_value'] ?? null,
            metadata: $data['metadata'] ?? null,
        );
    }
}
