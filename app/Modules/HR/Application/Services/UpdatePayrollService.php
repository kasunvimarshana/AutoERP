<?php

declare(strict_types=1);

namespace Modules\HR\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\HR\Application\Contracts\UpdatePayrollServiceInterface;
use Modules\HR\Application\DTOs\UpdatePayrollData;
use Modules\HR\Domain\Entities\PayrollRecord;
use Modules\HR\Domain\Events\PayrollRecordUpdated;
use Modules\HR\Domain\Exceptions\PayrollRecordNotFoundException;
use Modules\HR\Domain\RepositoryInterfaces\PayrollRepositoryInterface;

class UpdatePayrollService extends BaseService implements UpdatePayrollServiceInterface
{
    public function __construct(private readonly PayrollRepositoryInterface $payrollRepository)
    {
        parent::__construct($payrollRepository);
    }

    protected function handle(array $data): PayrollRecord
    {
        $dto    = UpdatePayrollData::fromArray($data);
        $id     = (int) ($dto->id ?? 0);
        $record = $this->payrollRepository->find($id);
        if (! $record) {
            throw new PayrollRecordNotFoundException($id);
        }

        $payPeriodStart = $dto->isProvided('pay_period_start') ? (string) $dto->pay_period_start : $record->getPayPeriodStart();
        $payPeriodEnd   = $dto->isProvided('pay_period_end') ? (string) $dto->pay_period_end : $record->getPayPeriodEnd();
        $grossSalary    = $dto->isProvided('gross_salary') ? (float) $dto->gross_salary : $record->getGrossSalary();
        $netSalary      = $dto->isProvided('net_salary') ? (float) $dto->net_salary : $record->getNetSalary();
        $deductions     = $dto->isProvided('deductions') ? (float) $dto->deductions : $record->getDeductions();
        $allowances     = $dto->isProvided('allowances') ? (float) $dto->allowances : $record->getAllowances();
        $bonuses        = $dto->isProvided('bonuses') ? (float) $dto->bonuses : $record->getBonuses();
        $currency       = $dto->isProvided('currency') ? (string) $dto->currency : $record->getCurrency();
        $notes          = $dto->isProvided('notes') ? $dto->notes : $record->getNotes();

        $record->updateDetails($payPeriodStart, $payPeriodEnd, $grossSalary, $netSalary, $deductions, $allowances, $bonuses, $currency, $notes);

        $saved = $this->payrollRepository->save($record);
        $this->addEvent(new PayrollRecordUpdated($saved));

        return $saved;
    }
}
