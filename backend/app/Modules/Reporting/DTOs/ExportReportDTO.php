<?php

namespace App\Modules\Reporting\DTOs;

use App\Modules\Reporting\Enums\ExportFormat;

/**
 * Export Report DTO
 */
class ExportReportDTO
{
    public function __construct(
        public int $reportId,
        public ExportFormat $format,
        public array $parameters = [],
        public ?string $filename = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            reportId: $data['report_id'],
            format: ExportFormat::from($data['format']),
            parameters: $data['parameters'] ?? [],
            filename: $data['filename'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'report_id' => $this->reportId,
            'format' => $this->format->value,
            'parameters' => $this->parameters,
            'filename' => $this->filename,
        ];
    }
}
