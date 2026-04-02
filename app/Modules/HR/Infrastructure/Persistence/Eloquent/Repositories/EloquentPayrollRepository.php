<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Facades\DB;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\HR\Domain\Entities\PayrollRecord;
use Modules\HR\Domain\RepositoryInterfaces\PayrollRepositoryInterface;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\PayrollModel;

class EloquentPayrollRepository extends EloquentRepository implements PayrollRepositoryInterface
{
    public function __construct(PayrollModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (PayrollModel $model): PayrollRecord => $this->mapModelToDomainEntity($model));
    }

    public function save(PayrollRecord $payrollRecord): PayrollRecord
    {
        $savedModel = null;

        DB::transaction(function () use ($payrollRecord, &$savedModel) {
            $data = [
                'tenant_id'        => $payrollRecord->getTenantId(),
                'employee_id'      => $payrollRecord->getEmployeeId(),
                'pay_period_start' => $payrollRecord->getPayPeriodStart(),
                'pay_period_end'   => $payrollRecord->getPayPeriodEnd(),
                'gross_salary'     => $payrollRecord->getGrossSalary(),
                'net_salary'       => $payrollRecord->getNetSalary(),
                'deductions'       => $payrollRecord->getDeductions(),
                'allowances'       => $payrollRecord->getAllowances(),
                'bonuses'          => $payrollRecord->getBonuses(),
                'currency'         => $payrollRecord->getCurrency(),
                'status'           => $payrollRecord->getStatus(),
                'notes'            => $payrollRecord->getNotes(),
                'metadata'         => $payrollRecord->getMetadata()->toArray(),
            ];

            if ($payrollRecord->getId()) {
                $savedModel = $this->update($payrollRecord->getId(), $data);
            } else {
                $savedModel = $this->model->create($data);
            }
        });

        if (! $savedModel instanceof PayrollModel) {
            throw new \RuntimeException('Failed to save payroll record.');
        }

        return $this->mapModelToDomainEntity($savedModel);
    }

    public function getByEmployee(int $employeeId): array
    {
        return $this->model->where('employee_id', $employeeId)
            ->get()
            ->map(fn ($m) => $this->mapModelToDomainEntity($m))
            ->all();
    }

    private function mapModelToDomainEntity(PayrollModel $model): PayrollRecord
    {
        return new PayrollRecord(
            tenantId:       $model->tenant_id,
            employeeId:     $model->employee_id,
            payPeriodStart: $model->pay_period_start instanceof \DateTimeInterface ? $model->pay_period_start->format('Y-m-d') : (string) $model->pay_period_start,
            payPeriodEnd:   $model->pay_period_end instanceof \DateTimeInterface ? $model->pay_period_end->format('Y-m-d') : (string) $model->pay_period_end,
            grossSalary:    (float) $model->gross_salary,
            netSalary:      (float) $model->net_salary,
            deductions:     (float) ($model->deductions ?? 0.0),
            allowances:     (float) ($model->allowances ?? 0.0),
            bonuses:        (float) ($model->bonuses ?? 0.0),
            currency:       (string) ($model->currency ?? 'USD'),
            status:         (string) ($model->status ?? 'draft'),
            notes:          $model->notes,
            metadata:       new Metadata(is_array($model->metadata) ? $model->metadata : []),
            id:             $model->id,
            createdAt:      $model->created_at ? new \DateTimeImmutable($model->created_at instanceof \DateTimeInterface ? $model->created_at->format('Y-m-d H:i:s') : $model->created_at) : null,
            updatedAt:      $model->updated_at ? new \DateTimeImmutable($model->updated_at instanceof \DateTimeInterface ? $model->updated_at->format('Y-m-d H:i:s') : $model->updated_at) : null,
        );
    }
}
