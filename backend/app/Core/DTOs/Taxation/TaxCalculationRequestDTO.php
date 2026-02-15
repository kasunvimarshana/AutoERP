<?php

declare(strict_types=1);

namespace App\Core\DTOs\Taxation;

use App\Core\DTOs\BaseDTO;

class TaxCalculationRequestDTO extends BaseDTO
{
    public function __construct(
        public readonly float $amount,
        public readonly ?int $productId = null,
        public readonly ?int $customerId = null,
        public readonly ?int $branchId = null,
        public readonly ?int $taxRateId = null,
        public readonly ?int $taxGroupId = null,
        public readonly bool $isInclusive = false,
        public readonly ?string $countryCode = null,
        public readonly ?string $stateCode = null,
        public readonly ?string $cityName = null,
        public readonly ?string $postalCode = null,
        public readonly ?array $metadata = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            amount: (float) $data['amount'],
            productId: isset($data['product_id']) ? (int) $data['product_id'] : null,
            customerId: isset($data['customer_id']) ? (int) $data['customer_id'] : null,
            branchId: isset($data['branch_id']) ? (int) $data['branch_id'] : null,
            taxRateId: isset($data['tax_rate_id']) ? (int) $data['tax_rate_id'] : null,
            taxGroupId: isset($data['tax_group_id']) ? (int) $data['tax_group_id'] : null,
            isInclusive: (bool) ($data['is_inclusive'] ?? false),
            countryCode: $data['country_code'] ?? null,
            stateCode: $data['state_code'] ?? null,
            cityName: $data['city_name'] ?? null,
            postalCode: $data['postal_code'] ?? null,
            metadata: $data['metadata'] ?? null,
        );
    }
}
