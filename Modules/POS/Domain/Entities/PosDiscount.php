<?php

namespace Modules\POS\Domain\Entities;

use Modules\POS\Domain\Enums\DiscountType;

readonly class PosDiscount
{
    public function __construct(
        public string        $id,
        public string        $tenant_id,
        public string        $code,
        public string        $name,
        public DiscountType  $type,
        public string        $value,
        public ?int          $usage_limit,
        public int           $times_used,
        public ?string       $expires_at,
        public bool          $is_active,
        public ?string       $description,
    ) {}
}
