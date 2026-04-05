<?php
declare(strict_types=1);
namespace Modules\UoM\Application\Contracts;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\UoM\Domain\Entities\UnitOfMeasure;
interface UnitOfMeasureServiceInterface {
    public function findById(int $id): UnitOfMeasure;
    public function findByTenant(int $tenantId, int $perPage = 15, int $page = 1): LengthAwarePaginator;
    public function create(array $data): UnitOfMeasure;
    public function update(int $id, array $data): UnitOfMeasure;
    public function delete(int $id): bool;
}
