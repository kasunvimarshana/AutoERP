<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\HR\Domain\Entities\Payslip;

class PayslipResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Payslip $entity */
        $entity = $this->resource;

        return [
            'id' => $entity->getId(),
            'tenant_id' => $entity->getTenantId(),
            'employee_id' => $entity->getEmployeeId(),
            'payroll_run_id' => $entity->getPayrollRunId(),
            'period_start' => $entity->getPeriodStart()->format('Y-m-d'),
            'period_end' => $entity->getPeriodEnd()->format('Y-m-d'),
            'gross_salary' => $entity->getGrossSalary(),
            'total_deductions' => $entity->getTotalDeductions(),
            'net_salary' => $entity->getNetSalary(),
            'base_salary' => $entity->getBaseSalary(),
            'worked_days' => $entity->getWorkedDays(),
            'status' => $entity->getStatus(),
            'journal_entry_id' => $entity->getJournalEntryId(),
            'lines' => PayslipLineResource::collection($entity->getLines()),
            'metadata' => $entity->getMetadata(),
            'created_at' => $entity->getCreatedAt()->format('c'),
            'updated_at' => $entity->getUpdatedAt()->format('c'),
        ];
    }
}
