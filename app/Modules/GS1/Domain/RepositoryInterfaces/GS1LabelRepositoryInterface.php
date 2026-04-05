<?php
declare(strict_types=1);
namespace Modules\GS1\Domain\RepositoryInterfaces;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\GS1\Domain\Entities\Gs1Label;
interface Gs1LabelRepositoryInterface {
    public function findById(int $id): ?Gs1Label;
    public function findByValue(string $value): ?Gs1Label;
    public function findByProduct(int $tenantId, int $productId): LengthAwarePaginator;
    public function create(array $data): Gs1Label;
    public function update(int $id, array $data): ?Gs1Label;
    public function delete(int $id): bool;
}
