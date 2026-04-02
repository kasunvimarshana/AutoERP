<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PayrollResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'               => $this->getId(),
            'tenant_id'        => $this->getTenantId(),
            'employee_id'      => $this->getEmployeeId(),
            'pay_period_start' => $this->getPayPeriodStart(),
            'pay_period_end'   => $this->getPayPeriodEnd(),
            'gross_salary'     => $this->getGrossSalary(),
            'net_salary'       => $this->getNetSalary(),
            'deductions'       => $this->getDeductions(),
            'allowances'       => $this->getAllowances(),
            'bonuses'          => $this->getBonuses(),
            'currency'         => $this->getCurrency(),
            'status'           => $this->getStatus(),
            'notes'            => $this->getNotes(),
            'metadata'         => $this->getMetadata()->toArray(),
            'created_at'       => $this->getCreatedAt()?->format('c'),
            'updated_at'       => $this->getUpdatedAt()?->format('c'),
        ];
    }
}
