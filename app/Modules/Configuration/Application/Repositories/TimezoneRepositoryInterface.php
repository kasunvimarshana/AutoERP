<?php

declare(strict_types=1);

namespace Modules\Configuration\Application\Repositories;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Repositories\Contracts\RepositoryPortInterface;

interface TimezoneRepositoryInterface extends RepositoryPortInterface
{
    public function findByName(string $name): ?DataRecord;
}
