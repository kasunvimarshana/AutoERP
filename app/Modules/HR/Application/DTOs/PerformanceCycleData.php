<?php

declare(strict_types=1);

namespace Modules\HR\Application\DTOs;

class PerformanceCycleData
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public readonly int $tenantId,
        public readonly string $name,
        public readonly string $periodStart,
        public readonly string $periodEnd,
        public readonly bool $isActive = true,
        public readonly array $metadata = [],
        public readonly ?int $id = null,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): static
    {
        return new static(
            tenantId: (int) $data['tenant_id'],
            name: (string) $data['name'],
            periodStart: (string) $data['period_start'],
            periodEnd: (string) $data['period_end'],
            isActive: isset($data['is_active']) ? (bool) $data['is_active'] : true,
            metadata: isset($data['metadata']) && is_array($data['metadata']) ? $data['metadata'] : [],
            id: isset($data['id']) ? (int) $data['id'] : null,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenantId,
            'name' => $this->name,
            'period_start' => $this->periodStart,
            'period_end' => $this->periodEnd,
            'is_active' => $this->isActive,
            'metadata' => $this->metadata,
        ];
    }
}
