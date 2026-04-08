<?php

declare(strict_types=1);

namespace Modules\Brand\Domain\RepositoryInterfaces;

use Modules\Brand\Domain\Entities\BrandLogo;
use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;

interface BrandLogoRepositoryInterface extends RepositoryInterface
{
    public function findByUuid(string $uuid): ?BrandLogo;

    public function findByBrand(int $brandId): ?BrandLogo;

    public function save(BrandLogo $logo): BrandLogo;

    public function deleteByBrand(int $brandId): bool;
}
