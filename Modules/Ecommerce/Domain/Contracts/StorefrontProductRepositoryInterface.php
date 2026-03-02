<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Domain\Contracts;

use Modules\Ecommerce\Domain\Entities\StorefrontProduct;

interface StorefrontProductRepositoryInterface
{
    public function save(StorefrontProduct $product): StorefrontProduct;

    public function findById(int $id, int $tenantId): ?StorefrontProduct;

    public function findBySlug(string $slug, int $tenantId): ?StorefrontProduct;

    public function findAll(int $tenantId, int $page, int $perPage): array;

    public function findFeatured(int $tenantId): array;

    public function delete(int $id, int $tenantId): void;
}
