<?php

declare(strict_types=1);

namespace Modules\HR\Application\UseCases;

use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\HR\Application\DTOs\PayrollData;
use Modules\HR\Domain\Entities\PayrollRecord;
use Modules\HR\Domain\Events\PayrollRecordCreated;
use Modules\HR\Domain\RepositoryInterfaces\PayrollRepositoryInterface;

class CreatePayrollRecord
{
    public function __construct(private readonly PayrollRepositoryInterface $repo) {}

    public function execute(PayrollData $data): PayrollRecord
    {
        $record = new PayrollRecord(
            tenantId:       $data->tenant_id,
            employeeId:     $data->employee_id,
            payPeriodStart: $data->pay_period_start,
            payPeriodEnd:   $data->pay_period_end,
            grossSalary:    $data->gross_salary,
            netSalary:      $data->net_salary,
            deductions:     $data->deductions ?? 0.0,
            allowances:     $data->allowances ?? 0.0,
            bonuses:        $data->bonuses ?? 0.0,
            currency:       $data->currency ?? 'USD',
            status:         $data->status ?? 'draft',
            notes:          $data->notes,
            metadata:       $data->metadata !== null ? new Metadata($data->metadata) : null,
        );

        $saved = $this->repo->save($record);
        PayrollRecordCreated::dispatch($saved);

        return $saved;
    }
}
