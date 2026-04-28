<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Facades\DB;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\HR\Domain\Entities\Payslip;
use Modules\HR\Domain\Entities\PayslipLine;
use Modules\HR\Domain\RepositoryInterfaces\PayslipRepositoryInterface;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\PayslipLineModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\PayslipModel;

class EloquentPayslipRepository extends EloquentRepository implements PayslipRepositoryInterface
{
    public function __construct(PayslipModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (PayslipModel $m): Payslip => $this->map($m));
    }

    public function save(Payslip $e): Payslip
    {
        $data = [
            'tenant_id' => $e->getTenantId(),
            'employee_id' => $e->getEmployeeId(),
            'payroll_run_id' => $e->getPayrollRunId(),
            'period_start' => $e->getPeriodStart()->format('Y-m-d'),
            'period_end' => $e->getPeriodEnd()->format('Y-m-d'),
            'gross_salary' => $e->getGrossSalary(),
            'total_deductions' => $e->getTotalDeductions(),
            'net_salary' => $e->getNetSalary(),
            'base_salary' => $e->getBaseSalary(),
            'worked_days' => $e->getWorkedDays(),
            'status' => $e->getStatus(),
            'journal_entry_id' => $e->getJournalEntryId(),
            'metadata' => $e->getMetadata(),
        ];

        /** @var PayslipModel $saved */
        $saved = DB::transaction(function () use ($e, $data): PayslipModel {
            /** @var PayslipModel $model */
            $model = $e->getId() ? $this->update($e->getId(), $data) : $this->create($data);

            $lineCodes = [];
            foreach ($e->getLines() as $line) {
                $lineCodes[] = $line->getItemCode();

                PayslipLineModel::query()->updateOrCreate(
                    [
                        'tenant_id' => $e->getTenantId(),
                        'payslip_id' => (int) $model->id,
                        'item_code' => $line->getItemCode(),
                    ],
                    [
                        'payroll_item_id' => $line->getPayrollItemId(),
                        'item_name' => $line->getItemName(),
                        'type' => $line->getType(),
                        'amount' => $line->getAmount(),
                        'metadata' => $line->getMetadata(),
                    ]
                );
            }

            // Keep persisted lines aligned with the domain payload.
            PayslipLineModel::query()
                ->where('tenant_id', $e->getTenantId())
                ->where('payslip_id', (int) $model->id)
                ->whereNotIn('item_code', $lineCodes === [] ? ['__none__'] : $lineCodes)
                ->delete();

            /** @var PayslipModel $fresh */
            $fresh = $model->fresh(['lines']);

            return $fresh;
        });

        return $this->toDomainEntity($saved);
    }

    public function find(int|string $id, array $columns = ['*']): ?Payslip
    {
        /** @var PayslipModel|null $model */
        $model = $this->model->newQuery()
            ->with('lines')
            ->find($id, $columns);

        return $model ? $this->toDomainEntity($model) : null;
    }

    public function findByEmployeeAndRun(int $tenantId, int $employeeId, int $payrollRunId): ?Payslip
    {
        /** @var PayslipModel|null $m */
        $m = $this->model->newQuery()
            ->with('lines')
            ->where('tenant_id', $tenantId)
            ->where('employee_id', $employeeId)
            ->where('payroll_run_id', $payrollRunId)
            ->first();

        return $m ? $this->toDomainEntity($m) : null;
    }

    public function findByPayrollRun(int $tenantId, int $payrollRunId): array
    {
        /** @var array<int, Payslip> $payslips */
        $payslips = $this->model->newQuery()
            ->with('lines')
            ->where('tenant_id', $tenantId)
            ->where('payroll_run_id', $payrollRunId)
            ->get()
            ->map(fn (PayslipModel $m): Payslip => $this->toDomainEntity($m))
            ->all();

        return $payslips;
    }

    private function map(PayslipModel $m): Payslip
    {
        $now = fn ($v) => $v instanceof \DateTimeInterface ? $v : new \DateTimeImmutable($v ?? 'now');
        $slip = new Payslip($m->tenant_id, $m->employee_id, $m->payroll_run_id, $now($m->period_start), $now($m->period_end), $m->gross_salary ?? '0', $m->total_deductions ?? '0', $m->net_salary ?? '0', $m->base_salary ?? '0', (float) $m->worked_days, $m->status, $m->journal_entry_id, $m->metadata ?? [], $now($m->created_at), $now($m->updated_at), $m->id);

        $lines = $m->relationLoaded('lines')
            ? $m->lines
            : $m->lines()->get();

        foreach ($lines as $l) {
            $slip->addLine(new PayslipLine($l->payslip_id, (int) $l->payroll_item_id, $l->item_name, $l->item_code, $l->type, $l->amount, $l->metadata ?? [], $now($l->created_at), $now($l->updated_at), $l->id));
        }

        return $slip;
    }
}
