<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Application\Services;

use Modules\Ecommerce\Application\Commands\CreateStorefrontProductCommand;
use Modules\Ecommerce\Application\Commands\DeleteStorefrontProductCommand;
use Modules\Ecommerce\Application\Commands\UpdateStorefrontProductCommand;
use Modules\Ecommerce\Application\Handlers\CreateStorefrontProductHandler;
use Modules\Ecommerce\Application\Handlers\DeleteStorefrontProductHandler;
use Modules\Ecommerce\Application\Handlers\UpdateStorefrontProductHandler;
use Modules\Ecommerce\Domain\Contracts\StorefrontProductRepositoryInterface;
use Modules\Ecommerce\Domain\Entities\StorefrontProduct;

class StorefrontProductService
{
    public function __construct(
        private readonly StorefrontProductRepositoryInterface $repository,
        private readonly CreateStorefrontProductHandler $createHandler,
        private readonly UpdateStorefrontProductHandler $updateHandler,
        private readonly DeleteStorefrontProductHandler $deleteHandler,
    ) {}

    public function create(CreateStorefrontProductCommand $cmd): StorefrontProduct
    {
        return $this->createHandler->handle($cmd);
    }

    public function update(UpdateStorefrontProductCommand $cmd): StorefrontProduct
    {
        return $this->updateHandler->handle($cmd);
    }

    public function delete(DeleteStorefrontProductCommand $cmd): void
    {
        $this->deleteHandler->handle($cmd);
    }

    public function findById(int $id, int $tenantId): ?StorefrontProduct
    {
        return $this->repository->findById($id, $tenantId);
    }

    public function findBySlug(string $slug, int $tenantId): ?StorefrontProduct
    {
        return $this->repository->findBySlug($slug, $tenantId);
    }

    public function findAll(int $tenantId, int $page, int $perPage): array
    {
        return $this->repository->findAll($tenantId, $page, $perPage);
    }

    public function findFeatured(int $tenantId): array
    {
        return $this->repository->findFeatured($tenantId);
    }
}
