<?php

declare(strict_types=1);

namespace Modules\Tax\Application\Contracts;

use Modules\Core\Application\Contracts\ServiceInterface;

interface FindTransactionTaxServiceInterface extends ServiceInterface
{
    public function listByReference(int $tenantId, string $referenceType, int $referenceId): array;
}
