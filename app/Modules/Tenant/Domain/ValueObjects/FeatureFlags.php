<?php
namespace Modules\Tenant\Domain\ValueObjects;

class FeatureFlags
{
    public function __construct(
        public readonly bool $multiWarehouse = false,
        public readonly bool $batchTracking = false,
        public readonly bool $serialTracking = false,
        public readonly bool $gs1 = false,
        public readonly bool $multiCurrency = false,
        public readonly bool $ecommerce = false,
    ) {}

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
