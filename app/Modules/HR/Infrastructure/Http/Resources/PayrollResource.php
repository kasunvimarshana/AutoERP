<?php
declare(strict_types=1);
namespace Modules\HR\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\HR\Domain\Entities\PayrollRecord;

class PayrollResource extends JsonResource
{
    public function toArray($request): array
    {
        /** @var PayrollRecord $rec */
        $rec = $this->resource;
        return [
            'id'                 => $rec->getId(),
            'tenant_id'          => $rec->getTenantId(),
            'employee_id'        => $rec->getEmployeeId(),
            'period_year'        => $rec->getPeriodYear(),
            'period_month'       => $rec->getPeriodMonth(),
            'basic_salary'       => $rec->getBasicSalary(),
            'allowances'         => $rec->getAllowances(),
            'deductions'         => $rec->getDeductions(),
            'tax_amount'         => $rec->getTaxAmount(),
            'gross_salary'       => $rec->getGrossSalary(),
            'net_salary'         => $rec->getNetSalary(),
            'status'             => $rec->getStatus(),
            'payment_date'       => $rec->getPaymentDate()?->format('Y-m-d'),
            'payment_reference'  => $rec->getPaymentReference(),
            'breakdown'          => $rec->getBreakdown(),
            'processed_by_id'    => $rec->getProcessedById(),
            'processed_at'       => $rec->getProcessedAt()?->format('Y-m-d H:i:s'),
            'created_at'         => $rec->getCreatedAt()?->format('Y-m-d H:i:s'),
            'updated_at'         => $rec->getUpdatedAt()?->format('Y-m-d H:i:s'),
        ];
    }
}
