<?php

declare(strict_types=1);

namespace Modules\UoM\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\UoM\Domain\Entities\UomCategory;

interface UomCategoryRepositoryInterface extends RepositoryInterface
{
    public function save(UomCategory $category): UomCategory;
}
