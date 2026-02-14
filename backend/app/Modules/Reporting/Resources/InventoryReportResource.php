<?php

namespace App\Modules\Reporting\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Inventory Report Resource
 * 
 * Transforms inventory report data for API responses
 * 
 * @package App\Modules\Reporting\Resources
 */
class InventoryReportResource extends JsonResource
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
                'total_products' => $this->resource['summary']['total_products'] ?? 0,
                'total_skus' => $this->resource['summary']['total_skus'] ?? 0,
                'low_stock_items' => $this->resource['summary']['low_stock_items'] ?? 0,
                'out_of_stock_items' => $this->resource['summary']['out_of_stock_items'] ?? 0,
                'total_inventory_value' => round($this->resource['summary']['total_inventory_value'] ?? 0, 2),
            ],
            'products_by_status' => $this->resource['products_by_status'] ?? [],
            'skus_by_warehouse' => $this->resource['skus_by_warehouse'] ?? [],
            'stock_alerts' => [
                'low_stock_percentage' => round($this->resource['stock_alerts']['low_stock_percentage'] ?? 0, 2),
                'out_of_stock_percentage' => round($this->resource['stock_alerts']['out_of_stock_percentage'] ?? 0, 2),
            ],
        ];
    }
}
