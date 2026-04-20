<?php

declare(strict_types=1);

namespace Modules\Tax\Application\DTOs;

class RecordTransactionTaxesData
{
    /**
     * @param  list<array<string, mixed>>|null  $tax_lines
     */
    public function __construct(
        public readonly int $tenant_id,
        public readonly string $reference_type,
        public readonly int $reference_id,
        public readonly ?array $tax_lines = null,
        public readonly ?string $taxable_amount = null,
        public readonly ?int $tax_group_id = null,
        public readonly ?int $product_category_id = null,
        public readonly ?string $party_type = null,
        public readonly ?string $region = null,
        public readonly ?string $transaction_date = null,
        public readonly ?int $default_tax_account_id = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            tenant_id: (int) $data['tenant_id'],
            reference_type: (string) $data['reference_type'],
            reference_id: (int) $data['reference_id'],
            tax_lines: isset($data['tax_lines']) && is_array($data['tax_lines']) ? array_values($data['tax_lines']) : null,
            taxable_amount: isset($data['taxable_amount']) ? (string) $data['taxable_amount'] : null,
            tax_group_id: isset($data['tax_group_id']) ? (int) $data['tax_group_id'] : null,
            product_category_id: isset($data['product_category_id']) ? (int) $data['product_category_id'] : null,
            party_type: isset($data['party_type']) ? (string) $data['party_type'] : null,
            region: isset($data['region']) ? (string) $data['region'] : null,
            transaction_date: isset($data['transaction_date']) ? (string) $data['transaction_date'] : null,
            default_tax_account_id: isset($data['default_tax_account_id']) ? (int) $data['default_tax_account_id'] : null,
        );
    }
}
