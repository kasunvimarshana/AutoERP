<?php

declare(strict_types=1);

namespace Modules\Finance\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Finance\Domain\Entities\ArTransaction;

interface ArTransactionRepositoryInterface extends RepositoryInterface
{
    public function save(ArTransaction $arTransaction): ArTransaction;
}
