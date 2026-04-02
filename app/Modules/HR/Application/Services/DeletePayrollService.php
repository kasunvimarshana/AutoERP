<?php

declare(strict_types=1);

namespace Modules\HR\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\HR\Application\Contracts\DeletePayrollServiceInterface;
use Modules\HR\Domain\Events\PayrollRecordDeleted;
use Modules\HR\Domain\Exceptions\PayrollRecordNotFoundException;
use Modules\HR\Domain\RepositoryInterfaces\PayrollRepositoryInterface;

class DeletePayrollService extends BaseService implements DeletePayrollServiceInterface
{
    public function __construct(private readonly PayrollRepositoryInterface $payrollRepository)
    {
        parent::__construct($payrollRepository);
    }

    protected function handle(array $data): bool
    {
        $id     = $data['id'];
        $record = $this->payrollRepository->find($id);
        if (! $record) {
            throw new PayrollRecordNotFoundException($id);
        }

        $tenantId = $record->getTenantId();
        $deleted  = $this->payrollRepository->delete($id);
        if ($deleted) {
            $this->addEvent(new PayrollRecordDeleted($tenantId, $id));
        }

        return $deleted;
    }
}
