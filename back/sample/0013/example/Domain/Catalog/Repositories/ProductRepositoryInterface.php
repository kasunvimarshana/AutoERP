<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Repositories;

use App\Shared\Domain\ValueObjects\Uuid;
use App\Domain\Catalog\Entities\Product;

interface ProductRepositoryInterface
{
    public function findById(Uuid $id): ?Product;

    /** @return Product[] */
    public function findAll(): array;

    public function save(Product $product): void;

    public function delete(Uuid $id): void;
}
