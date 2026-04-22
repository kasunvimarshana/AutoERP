<?php

declare(strict_types=1);

namespace Modules\Finance\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Finance\Domain\Entities\ApTransaction;

interface ApTransactionRepositoryInterface extends RepositoryInterface
{
    public function save(ApTransaction $apTransaction): ApTransaction;

    public function getSupplierBalance(int $tenantId, int $supplierId): string;
}
