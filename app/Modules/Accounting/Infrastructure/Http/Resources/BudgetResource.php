<?php
declare(strict_types=1);
namespace Modules\Accounting\Infrastructure\Http\Resources;
use Illuminate\Http\Resources\Json\JsonResource;
class BudgetResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'           => $this->resource->id,
            'tenant_id'    => $this->resource->tenantId,
            'name'         => $this->resource->name,
            'fiscal_year'  => $this->resource->fiscalYear,
            'start_date'   => $this->resource->startDate->format('Y-m-d'),
            'end_date'     => $this->resource->endDate->format('Y-m-d'),
            'status'       => $this->resource->status,
            'total_amount' => $this->resource->totalAmount,
            'notes'        => $this->resource->notes,
            'created_at'   => $this->resource->createdAt,
            'updated_at'   => $this->resource->updatedAt,
        ];
    }
}
