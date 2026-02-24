<?php
namespace Modules\Sales\Domain\Entities;
class Customer
{
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly string $name,
        public readonly string $type,
        public readonly ?string $email,
        public readonly ?string $phone,
        public readonly string $creditLimit,
        public readonly string $status,
        public readonly ?string $priceListId,
        public readonly ?string $paymentTerms,
        public readonly string $currency,
    ) {}
}
