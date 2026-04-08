<?php

declare(strict_types=1);

namespace Modules\HR\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\HR\Application\Contracts\ProcessPayrollServiceInterface;
use Modules\HR\Domain\Events\PayrollRecordProcessed;
use Modules\HR\Domain\Exceptions\PayrollRecordNotFoundException;
use Modules\HR\Domain\RepositoryInterfaces\PayrollRepositoryInterface;

class ProcessPayrollService extends BaseService implements ProcessPayrollServiceInterface
{
    public function __construct(private readonly PayrollRepositoryInterface $payrollRepository)
    {
        parent::__construct($payrollRepository);
    }

    protected function handle(array $data): mixed
    {
        $id     = $data['id'];
        $record = $this->payrollRepository->find($id);
        if (! $record) {
            throw new PayrollRecordNotFoundException($id);
        }

        $record->process();

        $saved = $this->payrollRepository->save($record);
        $this->addEvent(new PayrollRecordProcessed($saved));

        return $saved;
    }
}
