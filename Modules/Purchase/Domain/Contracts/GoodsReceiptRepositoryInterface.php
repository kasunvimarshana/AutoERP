<?php
namespace Modules\Purchase\Domain\Contracts;
interface GoodsReceiptRepositoryInterface
{
    public function findById(string $id): ?object;
    public function paginate(array $filters, int $perPage = 15): object;
    public function create(array $data): object;
}
