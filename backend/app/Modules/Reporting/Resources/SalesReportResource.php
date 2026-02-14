<?php

namespace App\Modules\Reporting\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Sales Report Resource
 * 
 * Transforms sales report data for API responses
 * 
 * @package App\Modules\Reporting\Resources
 */
class SalesReportResource extends JsonResource
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
            'period' => [
                'start_date' => $this->resource['period']['start_date'] ?? null,
                'end_date' => $this->resource['period']['end_date'] ?? null,
            ],
            'pos_sales' => [
                'total_transactions' => $this->resource['pos_sales']['total_transactions'] ?? 0,
                'total_amount' => round($this->resource['pos_sales']['total_amount'] ?? 0, 2),
                'total_tax' => round($this->resource['pos_sales']['total_tax'] ?? 0, 2),
                'average_transaction' => round($this->resource['pos_sales']['average_transaction'] ?? 0, 2),
            ],
            'invoice_sales' => [
                'total_invoices' => $this->resource['invoice_sales']['total_invoices'] ?? 0,
                'total_amount' => round($this->resource['invoice_sales']['total_amount'] ?? 0, 2),
                'total_tax' => round($this->resource['invoice_sales']['total_tax'] ?? 0, 2),
                'average_invoice' => round($this->resource['invoice_sales']['average_invoice'] ?? 0, 2),
            ],
            'summary' => [
                'total_sales' => round($this->resource['summary']['total_sales'] ?? 0, 2),
                'total_tax' => round($this->resource['summary']['total_tax'] ?? 0, 2),
                'total_transactions' => $this->resource['summary']['total_transactions'] ?? 0,
                'average_sale' => round($this->resource['summary']['average_sale'] ?? 0, 2),
            ],
            'daily_breakdown' => $this->resource['daily_breakdown'] ?? [],
        ];
    }
}
