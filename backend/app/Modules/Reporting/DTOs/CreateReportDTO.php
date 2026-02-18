<?php

namespace App\Modules\Reporting\DTOs;

use App\Modules\Reporting\Enums\ReportType;

/**
 * Create Report DTO
 */
class CreateReportDTO
{
    public function __construct(
        public string $name,
        public ReportType $reportType,
        public string $module,
        public array $queryConfig,
        public ?string $description = null,
        public ?array $scheduleConfig = null,
        public bool $isPublic = false,
        public ?int $createdById = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            reportType: ReportType::from($data['report_type']),
            module: $data['module'],
            queryConfig: $data['query_config'],
            description: $data['description'] ?? null,
            scheduleConfig: $data['schedule_config'] ?? null,
            isPublic: $data['is_public'] ?? false,
            createdById: $data['created_by_id'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'report_type' => $this->reportType->value,
            'module' => $this->module,
            'query_config' => $this->queryConfig,
            'description' => $this->description,
            'schedule_config' => $this->scheduleConfig,
            'is_public' => $this->isPublic,
            'created_by_id' => $this->createdById,
        ];
    }
}
