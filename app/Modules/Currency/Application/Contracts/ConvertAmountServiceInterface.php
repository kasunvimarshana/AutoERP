<?php

declare(strict_types=1);

namespace Modules\Currency\Application\Contracts;

interface ConvertAmountServiceInterface
{
    public function convert(
        string $tenantId,
        float $amount,
        string $from,
        string $to,
        ?string $date = null,
    ): float;
}
