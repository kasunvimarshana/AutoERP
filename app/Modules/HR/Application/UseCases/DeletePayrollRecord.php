<?php

declare(strict_types=1);

namespace Modules\HR\Application\UseCases;

use Modules\HR\Domain\Events\PayrollRecordDeleted;
use Modules\HR\Domain\Exceptions\PayrollRecordNotFoundException;
use Modules\HR\Domain\RepositoryInterfaces\PayrollRepositoryInterface;

class DeletePayrollRecord
{
    public function __construct(private readonly PayrollRepositoryInterface $repo) {}

    public function execute(int $id): bool
    {
        $record = $this->repo->find($id);
        if (! $record) {
            throw new PayrollRecordNotFoundException($id);
        }

        $tenantId = $record->getTenantId();
        $deleted  = $this->repo->delete($id);
        if ($deleted) {
            PayrollRecordDeleted::dispatch($tenantId, $id);
        }

        return $deleted;
    }
}
