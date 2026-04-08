<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Contracts\Repositories;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;

interface ProductCategoryRepositoryInterface extends RepositoryInterface
{
    public function findByCode(string $code): mixed;
}
