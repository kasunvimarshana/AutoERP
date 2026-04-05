<?php
declare(strict_types=1);
namespace Modules\CRM\Domain\RepositoryInterfaces;
use Modules\CRM\Domain\Entities\Activity;
interface ActivityRepositoryInterface {
    public function findById(int $id): ?Activity;
    public function findAllByTenant(int $tenantId, array $filters = []): array;
    public function create(array $data): Activity;
    public function update(int $id, array $data): ?Activity;
    public function delete(int $id): bool;
}
