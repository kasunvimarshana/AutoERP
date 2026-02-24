<?php
namespace Modules\Purchase\Domain\Entities;
class Vendor
{
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly string $name,
        public readonly ?string $email,
        public readonly ?string $phone,
        public readonly ?string $taxId,
        public readonly string $currency,
        public readonly ?string $paymentTerms,
        public readonly string $status,
        public readonly ?string $rating,
    ) {}
}
