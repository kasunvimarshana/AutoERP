<?php
declare(strict_types=1);
namespace Modules\CRM\Domain\RepositoryInterfaces;
use Modules\CRM\Domain\Entities\Opportunity;
interface OpportunityRepositoryInterface {
    public function findById(int $id): ?Opportunity;
    public function findAllByTenant(int $tenantId, array $filters = []): array;
    public function findByStage(int $tenantId, string $stage): array;
    public function create(array $data): Opportunity;
    public function update(int $id, array $data): ?Opportunity;
    public function delete(int $id): bool;
}
