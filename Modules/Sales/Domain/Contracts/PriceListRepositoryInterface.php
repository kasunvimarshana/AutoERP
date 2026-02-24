<?php
namespace Modules\Sales\Domain\Contracts;

interface PriceListRepositoryInterface
{
    public function findById(string $id): ?object;
    public function paginate(array $filters, int $perPage = 15): object;
    public function create(array $data): object;
    public function update(string $id, array $data): object;
    public function delete(string $id): bool;
    public function addItem(array $data): object;
    public function findItem(string $priceListId, string $productId, ?string $variantId, string $qty): ?object;
    public function itemsForPriceList(string $priceListId): array;
}
