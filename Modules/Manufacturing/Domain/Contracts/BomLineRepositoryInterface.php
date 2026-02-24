<?php

namespace Modules\Manufacturing\Domain\Contracts;

use Illuminate\Support\Collection;
use Modules\Shared\Domain\Contracts\RepositoryInterface;

interface BomLineRepositoryInterface extends RepositoryInterface
{
    public function findByBom(string $bomId): Collection;
}
