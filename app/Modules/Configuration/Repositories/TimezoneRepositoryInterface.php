<?php

declare(strict_types=1);

namespace Modules\Configuration\Repositories;

use Modules\Core\Contracts\RepositoryPortInterface;
use Modules\Core\DTOs\DataRecord;

interface TimezoneRepositoryInterface extends RepositoryPortInterface
{
    public function findByName(string $name): ?DataRecord;
}
