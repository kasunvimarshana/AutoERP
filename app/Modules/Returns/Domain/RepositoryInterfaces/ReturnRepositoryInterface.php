<?php

declare(strict_types=1);

namespace Modules\Returns\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;

interface ReturnRepositoryInterface extends RepositoryInterface
{
    public function findByReference(string $reference, int $tenantId): mixed;

    public function findWithLines(int|string $id): mixed;
}
