<?php

declare(strict_types=1);

namespace Modules\Configuration\Application\Repositories;

use Modules\Core\Application\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface CurrencyRepositoryInterface extends BaseRepositoryInterface
{
    public function findByCode(string $code, array $with = []): ?Model;

    public function findByName(string $name, array $with = []): ?Model;

    public function getActive(array $with = []): Collection;

    public function getInactive(array $with = []): Collection;
}

