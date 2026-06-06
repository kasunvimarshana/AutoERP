<?php

declare(strict_types=1);

namespace Modules\Configuration\Repositories;

use Modules\Core\DTOs\DataRecord;
use Modules\Core\Repositories\Contracts\RepositoryPortInterface;

interface TimezoneRepositoryInterface extends RepositoryPortInterface
{
    public function findByName(string $name): ?DataRecord;
}
