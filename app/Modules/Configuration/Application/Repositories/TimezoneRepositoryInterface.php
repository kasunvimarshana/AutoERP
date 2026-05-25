<?php

declare(strict_types=1);

namespace Modules\Configuration\Application\Repositories;

use Modules\Core\Application\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Model;

interface TimezoneRepositoryInterface extends BaseRepositoryInterface
{
    public function findByName(string $name, array $with = []): ?Model;
}

