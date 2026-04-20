<?php

declare(strict_types=1);

namespace Modules\Tax\Application\DTOs;

class TaxResolveData
{
    public function __construct(
        public readonly int $tenant_id,
        public readonly string $taxable_amount,
        public readonly ?int $tax_group_id = null,
        public readonly ?int $product_category_id = null,
        public readonly ?string $party_type = null,
        public readonly ?string $region = null,
        public readonly ?string $transaction_date = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            tenant_id: (int) $data['tenant_id'],
            taxable_amount: (string) $data['taxable_amount'],
            tax_group_id: isset($data['tax_group_id']) ? (int) $data['tax_group_id'] : null,
            product_category_id: isset($data['product_category_id']) ? (int) $data['product_category_id'] : null,
            party_type: isset($data['party_type']) ? (string) $data['party_type'] : null,
            region: isset($data['region']) ? (string) $data['region'] : null,
            transaction_date: isset($data['transaction_date']) ? (string) $data['transaction_date'] : null,
        );
    }
}
