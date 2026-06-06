<?php

declare(strict_types=1);

namespace Modules\Configuration\Repositories;

use Modules\Core\DTOs\DataRecord;
use Modules\Core\Repositories\Contracts\RepositoryPortInterface;

interface CurrencyRepositoryInterface extends RepositoryPortInterface
{
    public function findByCode(string $code): ?DataRecord;
}
