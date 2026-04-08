<?php

declare(strict_types=1);

namespace Modules\HR\Application\UseCases;

use Modules\HR\Application\DTOs\UpdatePayrollData;
use Modules\HR\Domain\Entities\PayrollRecord;
use Modules\HR\Domain\Events\PayrollRecordUpdated;
use Modules\HR\Domain\Exceptions\PayrollRecordNotFoundException;
use Modules\HR\Domain\RepositoryInterfaces\PayrollRepositoryInterface;

class UpdatePayrollRecord
{
    public function __construct(private readonly PayrollRepositoryInterface $repo) {}

    public function execute(UpdatePayrollData $data): PayrollRecord
    {
        $id     = (int) ($data->id ?? 0);
        $record = $this->repo->find($id);
        if (! $record) {
            throw new PayrollRecordNotFoundException($id);
        }

        $payPeriodStart = $data->isProvided('pay_period_start') ? (string) $data->pay_period_start : $record->getPayPeriodStart();
        $payPeriodEnd   = $data->isProvided('pay_period_end') ? (string) $data->pay_period_end : $record->getPayPeriodEnd();
        $grossSalary    = $data->isProvided('gross_salary') ? (float) $data->gross_salary : $record->getGrossSalary();
        $netSalary      = $data->isProvided('net_salary') ? (float) $data->net_salary : $record->getNetSalary();
        $deductions     = $data->isProvided('deductions') ? (float) $data->deductions : $record->getDeductions();
        $allowances     = $data->isProvided('allowances') ? (float) $data->allowances : $record->getAllowances();
        $bonuses        = $data->isProvided('bonuses') ? (float) $data->bonuses : $record->getBonuses();
        $currency       = $data->isProvided('currency') ? (string) $data->currency : $record->getCurrency();
        $notes          = $data->isProvided('notes') ? $data->notes : $record->getNotes();

        $record->updateDetails($payPeriodStart, $payPeriodEnd, $grossSalary, $netSalary, $deductions, $allowances, $bonuses, $currency, $notes);

        $saved = $this->repo->save($record);
        PayrollRecordUpdated::dispatch($saved);

        return $saved;
    }
}
