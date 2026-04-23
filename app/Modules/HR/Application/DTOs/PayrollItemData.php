<?php

declare(strict_types=1);

namespace Modules\HR\Application\DTOs;

class PayrollItemData
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public readonly int $tenantId,
        public readonly string $name,
        public readonly string $code,
        public readonly string $type,
        public readonly string $calculationType,
        public readonly string $value,
        public readonly bool $isActive = true,
        public readonly bool $isTaxable = false,
        public readonly ?int $accountId = null,
        public readonly array $metadata = [],
        public readonly ?int $id = null,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): static
    {
        return new static(
            tenantId: (int) $data['tenant_id'],
            name: (string) $data['name'],
            code: (string) $data['code'],
            type: (string) $data['type'],
            calculationType: (string) $data['calculation_type'],
            value: (string) $data['value'],
            isActive: isset($data['is_active']) ? (bool) $data['is_active'] : true,
            isTaxable: isset($data['is_taxable']) ? (bool) $data['is_taxable'] : false,
            accountId: isset($data['account_id']) ? (int) $data['account_id'] : null,
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
            'code' => $this->code,
            'type' => $this->type,
            'calculation_type' => $this->calculationType,
            'value' => $this->value,
            'is_active' => $this->isActive,
            'is_taxable' => $this->isTaxable,
            'account_id' => $this->accountId,
            'metadata' => $this->metadata,
        ];
    }
}
