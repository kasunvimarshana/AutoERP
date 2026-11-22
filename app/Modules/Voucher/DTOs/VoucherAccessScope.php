<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

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
