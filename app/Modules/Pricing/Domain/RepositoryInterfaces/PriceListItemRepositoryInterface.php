<?php
namespace Modules\Pricing\Domain\RepositoryInterfaces;
use Modules\Pricing\Domain\Entities\PriceListItem;

interface PriceListItemRepositoryInterface
{
    public function findById(int $id): ?PriceListItem;
    public function findByPriceList(int $priceListId): array;
    public function findByProduct(int $priceListId, int $productId): ?PriceListItem;
    public function create(array $data): PriceListItem;
    public function update(PriceListItem $item, array $data): PriceListItem;
    public function delete(PriceListItem $item): bool;
}
