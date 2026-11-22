<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Finance\Contracts;

use Modules\Finance\DTOs\PostingResultData;

interface FinancePaymentReversalInterface
{
    public function reversePayment(
        int $tenantId,
        ?int $organizationUnitId,
        int $paymentId,
        string $reversalDate,
        ?int $reversedBy = null,
        ?string $reason = null,
    ): PostingResultData;
}
