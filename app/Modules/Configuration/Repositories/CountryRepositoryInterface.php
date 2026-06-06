<?php

declare(strict_types=1);

namespace Modules\Configuration\Repositories;

use Modules\Core\DTOs\DataRecord;
use Modules\Core\Repositories\Contracts\RepositoryPortInterface;

interface CountryRepositoryInterface extends RepositoryPortInterface
{
    public function findByCode(string $code): ?DataRecord;
}
