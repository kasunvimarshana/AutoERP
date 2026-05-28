<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Contracts\Services;

use Modules\Core\Application\Results\Result;

interface PaymentTermServiceInterface
{
    public function calculateDueDate(int $paymentTermId, int $tenantId, string $baseDate): Result;
}
