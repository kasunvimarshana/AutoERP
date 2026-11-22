<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Payment\DTOs;

final readonly class PaymentMethodData
{
    public function __construct(
        public int $id,
        public string $code,
        public string $name,
        public string $type,
        public bool $requiresReference,
        public bool $requiresInstrumentDetails,
    ) {}
}
