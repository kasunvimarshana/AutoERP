<?php

declare(strict_types=1);

namespace Modules\Purchase\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Purchase\Domain\Entities\GrnHeader;

interface GrnHeaderRepositoryInterface extends RepositoryInterface
{
    public function save(GrnHeader $header): GrnHeader;

    public function find(int|string $id, array $columns = ['*']): ?GrnHeader;
}
