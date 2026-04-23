<?php

declare(strict_types=1);

namespace Modules\HR\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\HR\Domain\Entities\EmployeeDocument;

interface EmployeeDocumentRepositoryInterface extends RepositoryInterface
{
    public function save(EmployeeDocument $document): EmployeeDocument;

    public function find(int|string $id, array $columns = ['*']): ?EmployeeDocument;

    /** @return EmployeeDocument[] */
    public function findByEmployee(int $tenantId, int $employeeId): array;
}
