<?php

declare(strict_types=1);

namespace Modules\CRM\Domain\Contracts\Repositories;

use Illuminate\Support\Collection;
use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;

interface ContactRepositoryInterface extends RepositoryInterface
{
    /**
     * Find all contacts for a given polymorphic owner.
     */
    public function findByContactable(string $type, string $id): Collection;
}
