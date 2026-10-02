<?php

declare(strict_types=1);

namespace Modules\VehicleService\DTOs;

final readonly class VehicleServicePaymentData
{
    public function __construct(
        public int $expectedVersion,
        public int $invoiceId,
        public string $paymentDate,
        public array $lines,
        public ?int $currencyId = null,
        public string $exchangeRate = '1.000000',
        public ?int $createdBy = null,
    ) {}
}
