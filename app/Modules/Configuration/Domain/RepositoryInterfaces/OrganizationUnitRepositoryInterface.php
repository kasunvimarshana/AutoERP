<?php
namespace Modules\Configuration\Domain\RepositoryInterfaces;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Configuration\Domain\Entities\OrganizationUnit;

interface OrganizationUnitRepositoryInterface
{
    public function findById(int $id): ?OrganizationUnit;
    public function findByCode(int $tenantId, string $code): ?OrganizationUnit;
    public function findAll(int $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator;
    public function create(array $data): OrganizationUnit;
    public function update(OrganizationUnit $unit, array $data): OrganizationUnit;
    public function delete(OrganizationUnit $unit): bool;
}
