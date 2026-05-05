<?php

declare(strict_types=1);

namespace Modules\Customer\Application\DTOs;

class CustomerData
{
    /**
     * @param  array<string, mixed>|null  $metadata
     * @param  array<string, mixed>|null  $user
     */
    public function __construct(
        public readonly int $tenantId,
        public readonly ?int $userId = null,
        public readonly ?string $customerCode = null,
        public readonly string $name = '',
        public readonly string $type = 'company',
        public readonly ?int $orgUnitId = null,
        public readonly ?string $taxNumber = null,
        public readonly ?string $registrationNumber = null,
        public readonly ?int $currencyId = null,
        public readonly string $creditLimit = '0.000000',
        public readonly int $paymentTermsDays = 30,
        public readonly ?int $arAccountId = null,
        public readonly string $status = 'active',
        public readonly ?string $notes = null,
        public readonly ?array $metadata = null,
        public readonly ?array $user = null,
        public readonly ?int $id = null,
        public readonly int $rowVersion = 1,
    )
    {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            tenantId: (int) $data['tenant_id'],
            userId: isset($data['user_id']) ? (int) $data['user_id'] : null,
            customerCode: isset($data['customer_code']) ? (string) $data['customer_code'] : null,
            name: isset($data['name']) ? trim((string) $data['name']) : '',
            type: isset($data['type']) ? (string) $data['type'] : 'company',
            orgUnitId: isset($data['org_unit_id']) ? (int) $data['org_unit_id'] : null,
            taxNumber: isset($data['tax_number']) ? (string) $data['tax_number'] : null,
            registrationNumber: isset($data['registration_number']) ? (string) $data['registration_number'] : null,
            currencyId: isset($data['currency_id']) ? (int) $data['currency_id'] : null,
            creditLimit: isset($data['credit_limit']) ? (string) $data['credit_limit'] : '0.000000',
            paymentTermsDays: isset($data['payment_terms_days']) ? (int) $data['payment_terms_days'] : 30,
            arAccountId: isset($data['ar_account_id']) ? (int) $data['ar_account_id'] : null,
            status: isset($data['status']) ? (string) $data['status'] : 'active',
            notes: isset($data['notes']) ? (string) $data['notes'] : null,
            metadata: isset($data['metadata']) && is_array($data['metadata']) ? $data['metadata'] : null,
            user: isset($data['user']) && is_array($data['user']) ? $data['user'] : null,
            id: isset($data['id']) ? (int) $data['id'] : null,
            rowVersion: isset($data['row_version']) ? (int) $data['row_version'] : 1,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenantId,
            'user_id' => $this->userId,
            'customer_code' => $this->customerCode,
            'name' => $this->name,
            'type' => $this->type,
            'org_unit_id' => $this->orgUnitId,
            'tax_number' => $this->taxNumber,
            'registration_number' => $this->registrationNumber,
            'currency_id' => $this->currencyId,
            'credit_limit' => $this->creditLimit,
            'payment_terms_days' => $this->paymentTermsDays,
            'ar_account_id' => $this->arAccountId,
            'status' => $this->status,
            'notes' => $this->notes,
            'metadata' => $this->metadata,
            'user' => $this->user,
        ];
    }
}
