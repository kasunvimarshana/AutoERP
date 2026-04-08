<?php

declare(strict_types=1);

namespace Modules\HR\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\HR\Application\Contracts\CreatePayrollServiceInterface;
use Modules\HR\Application\DTOs\PayrollData;
use Modules\HR\Domain\Entities\PayrollRecord;
use Modules\HR\Domain\Events\PayrollRecordCreated;
use Modules\HR\Domain\RepositoryInterfaces\PayrollRepositoryInterface;

class CreatePayrollService extends BaseService implements CreatePayrollServiceInterface
{
    public function __construct(private readonly PayrollRepositoryInterface $payrollRepository)
    {
        parent::__construct($payrollRepository);
    }

    protected function handle(array $data): PayrollRecord
    {
        $dto = PayrollData::fromArray($data);

        $record = new PayrollRecord(
            tenantId:       $dto->tenant_id,
            employeeId:     $dto->employee_id,
            payPeriodStart: $dto->pay_period_start,
            payPeriodEnd:   $dto->pay_period_end,
            grossSalary:    $dto->gross_salary,
            netSalary:      $dto->net_salary,
            deductions:     $dto->deductions ?? 0.0,
            allowances:     $dto->allowances ?? 0.0,
            bonuses:        $dto->bonuses ?? 0.0,
            currency:       $dto->currency ?? 'USD',
            status:         $dto->status ?? 'draft',
            notes:          $dto->notes,
            metadata:       $dto->metadata !== null ? new Metadata($dto->metadata) : null,
        );

        $saved = $this->payrollRepository->save($record);
        $this->addEvent(new PayrollRecordCreated($saved));

        return $saved;
    }
}
