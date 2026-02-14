<?php

namespace App\Modules\Reporting\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Customer Report Resource
 * 
 * Transforms customer report data for API responses
 * 
 * @package App\Modules\Reporting\Resources
 */
class CustomerReportResource extends JsonResource
{
    /**
     * Transform the resource into an array
     *
     * @param \Illuminate\Http\Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'summary' => [
                'total_customers' => $this->resource['summary']['total_customers'] ?? 0,
                'active_customers' => $this->resource['summary']['active_customers'] ?? 0,
                'inactive_customers' => $this->resource['summary']['inactive_customers'] ?? 0,
                'customers_with_purchases' => $this->resource['summary']['customers_with_purchases'] ?? 0,
                'average_lifetime_value' => round($this->resource['summary']['average_lifetime_value'] ?? 0, 2),
            ],
            'customers_by_status' => $this->resource['customers_by_status'] ?? [],
            'engagement' => [
                'purchase_rate' => round($this->resource['engagement']['purchase_rate'] ?? 0, 2),
                'active_rate' => round($this->resource['engagement']['active_rate'] ?? 0, 2),
            ],
        ];
    }
}
