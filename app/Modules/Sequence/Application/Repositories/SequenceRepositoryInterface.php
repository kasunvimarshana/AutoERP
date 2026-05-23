<?php

declare(strict_types=1);

namespace Modules\Sequence\Application\Repositories;

use App\Support\Repositories\BaseRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface SequenceRepositoryInterface extends BaseRepositoryInterface
{
    public function getForTenant(int|string $tenantId, array $with = []): Collection;

    public function paginateForTenant(int|string $tenantId, int $perPage = 15, array $with = []): LengthAwarePaginator;

    public function findForTenantById(int|string $tenantId, int|string $id, array $with = []): ?Model;

    public function getForOrganizationUnit(int|string $organizationUnitId, array $with = []): Collection;

    public function paginateForOrganizationUnit(
        int|string $organizationUnitId,
        int $perPage = 15,
        array $with = [],
    ): LengthAwarePaginator;

    public function findForScopeDocumentAndPeriod(
        int|string $tenantId,
        int|string|null $organizationUnitId,
        string $documentType,
        ?string $periodValue,
        bool $fallbackToGlobal = true,
        array $with = [],
    ): ?Model;

    public function lockForScopeDocumentAndPeriod(
        int|string $tenantId,
        int|string|null $organizationUnitId,
        string $documentType,
        ?string $periodValue,
        bool $fallbackToGlobal = true,
        array $with = [],
    ): ?Model;

    public function findDefinitionForScopeDocument(
        int|string $tenantId,
        int|string|null $organizationUnitId,
        string $documentType,
        bool $fallbackToGlobal = true,
        array $with = [],
    ): ?Model;
}
