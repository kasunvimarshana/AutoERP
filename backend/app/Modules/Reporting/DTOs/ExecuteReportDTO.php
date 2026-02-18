<?php

namespace App\Modules\Reporting\DTOs;

/**
 * Execute Report DTO
 */
class ExecuteReportDTO
{
    public function __construct(
        public int $reportId,
        public array $parameters = [],
        public ?int $executedById = null,
        public ?string $startDate = null,
        public ?string $endDate = null,
        public ?int $limit = null,
        public ?int $page = 1,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            reportId: $data['report_id'],
            parameters: $data['parameters'] ?? [],
            executedById: $data['executed_by_id'] ?? null,
            startDate: $data['start_date'] ?? null,
            endDate: $data['end_date'] ?? null,
            limit: $data['limit'] ?? null,
            page: $data['page'] ?? 1,
        );
    }

    public function toArray(): array
    {
        return [
            'report_id' => $this->reportId,
            'parameters' => $this->parameters,
            'executed_by_id' => $this->executedById,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'limit' => $this->limit,
            'page' => $this->page,
        ];
    }
}
