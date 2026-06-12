<?php

declare(strict_types=1);

namespace Modules\Payment\DTOs;

use Modules\Finance\DTOs\FinancePostingLine;

final readonly class PaymentPostingRequest
{
    /**
     * @param  list<FinancePostingLine>  $lines
     */
    public function __construct(
        public int $paymentId,
        public string $paymentType,
        public string $paymentDate,
        public ?int $currencyId = null,
        public string $exchangeRate = '1.000000',
        public array $lines = [],
        public ?string $postingProfileCode = null,
    ) {}
}
