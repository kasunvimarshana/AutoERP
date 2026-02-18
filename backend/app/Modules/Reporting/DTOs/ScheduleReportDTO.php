<?php

namespace App\Modules\Reporting\DTOs;

use App\Modules\Reporting\Enums\ExportFormat;

/**
 * Schedule Report DTO
 */
class ScheduleReportDTO
{
    public function __construct(
        public int $reportId,
        public string $scheduleCron,
        public array $recipients,
        public ExportFormat $format,
        public bool $isActive = true,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            reportId: $data['report_id'],
            scheduleCron: $data['schedule_cron'],
            recipients: $data['recipients'],
            format: ExportFormat::from($data['format']),
            isActive: $data['is_active'] ?? true,
        );
    }

    public function toArray(): array
    {
        return [
            'report_id' => $this->reportId,
            'schedule_cron' => $this->scheduleCron,
            'recipients' => $this->recipients,
            'format' => $this->format->value,
            'is_active' => $this->isActive,
        ];
    }
}
