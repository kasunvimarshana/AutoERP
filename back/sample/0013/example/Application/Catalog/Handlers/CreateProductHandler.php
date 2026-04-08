<?php

declare(strict_types=1);

namespace App\Application\Catalog\Handlers;

use App\Application\Catalog\Commands\CreateProductCommand;
use App\Domain\Catalog\Entities\Product;
use App\Domain\Catalog\Repositories\ProductRepositoryInterface;
use App\Domain\Catalog\ValueObjects\Money;
use App\Domain\Catalog\ValueObjects\ProductName;

final class CreateProductHandler
{
    public function __construct(
        private readonly ProductRepositoryInterface $repository,
    ) {}

    public function handle(CreateProductCommand $command): Product
    {
        $product = Product::create(
            name:  ProductName::fromString($command->name),
            price: Money::of($command->priceAmount, $command->priceCurrency),
        );

        $this->repository->save($product);

        return $product;
    }
}
