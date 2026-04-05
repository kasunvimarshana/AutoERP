<?php
declare(strict_types=1);
namespace Modules\CRM\Domain\RepositoryInterfaces;
use Modules\CRM\Domain\Entities\Lead;
interface LeadRepositoryInterface {
    public function findById(int $id): ?Lead;
    public function findAllByTenant(int $tenantId, array $filters = []): array;
    public function create(array $data): Lead;
    public function update(int $id, array $data): ?Lead;
    public function delete(int $id): bool;
}
