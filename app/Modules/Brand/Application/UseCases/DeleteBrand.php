<?php

declare(strict_types=1);

namespace Modules\Brand\Application\UseCases;

use Modules\Brand\Domain\Events\BrandDeleted;
use Modules\Brand\Domain\Exceptions\BrandNotFoundException;
use Modules\Brand\Domain\RepositoryInterfaces\BrandRepositoryInterface;

class DeleteBrand
{
    public function __construct(private readonly BrandRepositoryInterface $brandRepo) {}

    public function execute(int $id): bool
    {
        $brand = $this->brandRepo->find($id);
        if (! $brand) {
            throw new BrandNotFoundException($id);
        }

        $tenantId = $brand->getTenantId();
        $deleted = $this->brandRepo->delete($id);

        if ($deleted) {
            event(new BrandDeleted($id, $tenantId));
        }

        return $deleted;
    }
}
