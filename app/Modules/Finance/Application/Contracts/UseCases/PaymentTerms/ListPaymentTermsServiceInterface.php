<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Contracts\UseCases\PaymentTerms;

use Modules\Core\Application\Results\Result;

interface ListPaymentTermsServiceInterface
{
    /**
     * @param array<string, mixed> $criteria
     */
    public function execute(array $criteria, int $perPage, int $page): Result;
}
