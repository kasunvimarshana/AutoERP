<?php
declare(strict_types=1);
namespace Modules\Maintenance\Domain\RepositoryInterfaces;
use Modules\Maintenance\Domain\Entities\ServiceOrder;
interface ServiceOrderRepositoryInterface {
    public function findById(int $id): ?ServiceOrder;
    public function findByNumber(int $tenantId, string $number): ?ServiceOrder;
    public function findAllByTenant(int $tenantId, array $filters = []): array;
    public function create(array $data): ServiceOrder;
    public function update(int $id, array $data): ?ServiceOrder;
    public function delete(int $id): bool;
}
