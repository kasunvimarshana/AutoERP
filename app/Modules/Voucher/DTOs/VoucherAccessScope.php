<?php

declare(strict_types=1);

namespace Modules\Voucher\DTOs;

final readonly class VoucherAccessScope
{
    public function __construct(
        public bool $payments,
        public bool $finance,
    ) {}

    public function any(): bool
    {
        return $this->payments || $this->finance;
    }
}
