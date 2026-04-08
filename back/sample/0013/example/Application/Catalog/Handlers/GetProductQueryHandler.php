<?php

declare(strict_types=1);

namespace App\Application\Catalog\Handlers;

use App\Application\Catalog\Queries\GetProductQuery;
use App\Domain\Catalog\Entities\Product;
use App\Domain\Catalog\Exceptions\InvalidProductException;
use App\Domain\Catalog\Repositories\ProductRepositoryInterface;
use App\Shared\Domain\ValueObjects\Uuid;

final class GetProductQueryHandler
{
    public function __construct(
        private readonly ProductRepositoryInterface $repository,
    ) {}

    public function handle(GetProductQuery $query): Product
    {
        $product = $this->repository->findById(Uuid::fromString($query->productId));

        if ($product === null) {
            throw InvalidProductException::notFound(Uuid::fromString($query->productId));
        }

        return $product;
    }
}
