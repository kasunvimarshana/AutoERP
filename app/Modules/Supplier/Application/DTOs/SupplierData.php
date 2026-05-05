<?php

declare(strict_types=1);

namespace Modules\Supplier\Application\DTOs;

class SupplierData
{
    /**
     * @param  array<string, mixed>|null  $metadata
     * @param  array<string, mixed>|null  $user
     */
    public function __construct(
        public readonly int $tenant_id,
        public readonly ?int $user_id = null,
        public readonly ?string $supplier_code = null,
        public readonly string $name = '',
        public readonly string $type = 'company',
        public readonly ?int $org_unit_id = null,
        public readonly ?string $tax_number = null,
        public readonly ?string $registration_number = null,
        public readonly ?int $currency_id = null,
        public readonly int $payment_terms_days = 30,
        public readonly ?int $ap_account_id = null,
        public readonly string $status = 'active',
        public readonly ?string $notes = null,
        public readonly ?array $metadata = null,
        public readonly ?array $user = null,
        public readonly ?int $id = null,
        public readonly int $row_version = 1,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            tenant_id: (int) $data['tenant_id'],
            user_id: isset($data['user_id']) ? (int) $data['user_id'] : null,
            supplier_code: isset($data['supplier_code']) ? (string) $data['supplier_code'] : null,
            name: isset($data['name']) ? trim((string) $data['name']) : '',
            type: isset($data['type']) ? (string) $data['type'] : 'company',
            org_unit_id: isset($data['org_unit_id']) ? (int) $data['org_unit_id'] : null,
            tax_number: isset($data['tax_number']) ? (string) $data['tax_number'] : null,
            registration_number: isset($data['registration_number']) ? (string) $data['registration_number'] : null,
            currency_id: isset($data['currency_id']) ? (int) $data['currency_id'] : null,
            payment_terms_days: isset($data['payment_terms_days']) ? (int) $data['payment_terms_days'] : 30,
            ap_account_id: isset($data['ap_account_id']) ? (int) $data['ap_account_id'] : null,
            status: isset($data['status']) ? (string) $data['status'] : 'active',
            notes: isset($data['notes']) ? (string) $data['notes'] : null,
            metadata: isset($data['metadata']) && is_array($data['metadata']) ? $data['metadata'] : null,
            user: isset($data['user']) && is_array($data['user']) ? $data['user'] : null,
            id: isset($data['id']) ? (int) $data['id'] : null,
            row_version: isset($data['row_version']) ? (int) $data['row_version'] : 1,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'user_id' => $this->user_id,
            'supplier_code' => $this->supplier_code,
            'name' => $this->name,
            'type' => $this->type,
            'org_unit_id' => $this->org_unit_id,
            'tax_number' => $this->tax_number,
            'registration_number' => $this->registration_number,
            'currency_id' => $this->currency_id,
            'payment_terms_days' => $this->payment_terms_days,
            'ap_account_id' => $this->ap_account_id,
            'status' => $this->status,
            'notes' => $this->notes,
            'metadata' => $this->metadata,
            'user' => $this->user,
        ];
    }
}
