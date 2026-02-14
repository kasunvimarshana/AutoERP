<?php

namespace App\Modules\Reporting\DTOs;

/**
 * Report Filter DTO
 * 
 * Data Transfer Object for report filtering parameters
 * 
 * @package App\Modules\Reporting\DTOs
 */
class ReportFilterDTO
{
    public ?string $startDate;
    public ?string $endDate;
    public ?string $period;
    public ?int $limit;
    public ?string $status;
    public ?int $customerId;
    public ?int $productId;
    public ?string $groupBy;
    public ?string $sortBy;
    public ?string $sortOrder;

    public function __construct(array $data = [])
    {
        $this->startDate = $data['start_date'] ?? $data['startDate'] ?? null;
        $this->endDate = $data['end_date'] ?? $data['endDate'] ?? null;
        $this->period = $data['period'] ?? null;
        $this->limit = isset($data['limit']) ? (int) $data['limit'] : null;
        $this->status = $data['status'] ?? null;
        $this->customerId = isset($data['customer_id']) ? (int) $data['customer_id'] : null;
        $this->productId = isset($data['product_id']) ? (int) $data['product_id'] : null;
        $this->groupBy = $data['group_by'] ?? $data['groupBy'] ?? null;
        $this->sortBy = $data['sort_by'] ?? $data['sortBy'] ?? 'created_at';
        $this->sortOrder = $data['sort_order'] ?? $data['sortOrder'] ?? 'desc';
    }

    public function toArray(): array
    {
        return array_filter([
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'period' => $this->period,
            'limit' => $this->limit,
            'status' => $this->status,
            'customer_id' => $this->customerId,
            'product_id' => $this->productId,
            'group_by' => $this->groupBy,
            'sort_by' => $this->sortBy,
            'sort_order' => $this->sortOrder,
        ], fn($value) => $value !== null);
    }

    public function hasDateRange(): bool
    {
        return !empty($this->startDate) && !empty($this->endDate);
    }

    public function hasPeriod(): bool
    {
        return !empty($this->period);
    }
}
