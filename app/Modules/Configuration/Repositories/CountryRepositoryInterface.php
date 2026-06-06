<?php

declare(strict_types=1);

namespace Modules\Configuration\Repositories;

use Modules\Core\Contracts\RepositoryPortInterface;
use Modules\Core\DTOs\DataRecord;

interface CountryRepositoryInterface extends RepositoryPortInterface
{
    public function findByCode(string $code): ?DataRecord;
}
