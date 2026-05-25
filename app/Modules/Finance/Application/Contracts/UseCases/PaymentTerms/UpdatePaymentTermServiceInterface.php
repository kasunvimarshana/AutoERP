<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Contracts\UseCases\PaymentTerms;

use Modules\Core\Application\Results\Result;

interface UpdatePaymentTermServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(int|string $id, array $payload): Result;
}
