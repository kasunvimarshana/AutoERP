<?php
declare(strict_types=1);
namespace Modules\Maintenance\Domain\RepositoryInterfaces;
use Modules\Maintenance\Domain\Entities\ServiceOrderLine;
interface ServiceOrderLineRepositoryInterface {
    public function findByServiceOrder(int $serviceOrderId): array;
    public function create(array $data): ServiceOrderLine;
    public function delete(int $id): bool;
}
