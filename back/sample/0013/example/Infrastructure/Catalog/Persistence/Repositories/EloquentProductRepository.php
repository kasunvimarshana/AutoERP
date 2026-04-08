<?php

declare(strict_types=1);

namespace App\Infrastructure\Catalog\Persistence\Repositories;

use App\Domain\Catalog\Entities\Product;
use App\Domain\Catalog\Repositories\ProductRepositoryInterface;
use App\Domain\Catalog\ValueObjects\Money;
use App\Domain\Catalog\ValueObjects\ProductName;
use App\Infrastructure\Catalog\Persistence\Eloquent\ProductModel;
use App\Shared\Domain\ValueObjects\Uuid;

/**
 * EloquentProductRepository — Infrastructure implementation of ProductRepositoryInterface.
 *
 * Translates between the Eloquent persistence model and the Product domain entity
 * using the Data Mapper pattern. Domain events are dispatched after each save.
 */
final class EloquentProductRepository implements ProductRepositoryInterface
{
    public function __construct(
        private readonly ProductModel $model,
    ) {}

    public function findById(Uuid $id): ?Product
    {
        $record = $this->model->newQuery()->find($id->value());

        return $record ? $this->fromModel($record) : null;
    }

    /** @return Product[] */
    public function findAll(): array
    {
        return $this->model->newQuery()
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($record) => $this->fromModel($record))
            ->all();
    }

    public function save(Product $product): void
    {
        $this->model->newQuery()->updateOrInsert(
            ['id' => $product->id()->value()],
            $this->toArray($product),
        );

        // Dispatch all queued domain events after successful persistence
        foreach ($product->releaseEvents() as $event) {
            event($event);
        }
    }

    public function delete(Uuid $id): void
    {
        $this->model->newQuery()->where('id', $id->value())->delete();
    }

    // -------------------------------------------------------------------------
    // Data Mapping
    // -------------------------------------------------------------------------

    private function fromModel(ProductModel $record): Product
    {
        return Product::reconstitute(
            id:        Uuid::fromString($record->id),
            name:      ProductName::fromString($record->name),
            price:     Money::of($record->price_amount, $record->price_currency),
            active:    (bool) $record->active,
            createdAt: new \DateTimeImmutable($record->created_at),
        );
    }

    private function toArray(Product $product): array
    {
        return [
            'id'             => $product->id()->value(),
            'name'           => $product->name()->value(),
            'price_amount'   => $product->price()->amount(),
            'price_currency' => $product->price()->currency(),
            'active'         => $product->isActive(),
        ];
    }
}
