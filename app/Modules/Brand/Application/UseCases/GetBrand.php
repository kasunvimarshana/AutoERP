<?php

declare(strict_types=1);

namespace Modules\Brand\Application\UseCases;

use Modules\Brand\Domain\Entities\Brand;
use Modules\Brand\Domain\RepositoryInterfaces\BrandRepositoryInterface;

class GetBrand
{
    public function __construct(private readonly BrandRepositoryInterface $brandRepo) {}

    public function execute(int $id): ?Brand
    {
        return $this->brandRepo->find($id);
    }
}
