<?php

declare(strict_types=1);

namespace Modules\Reporting\Application\DTOs;

use Modules\Core\Application\DTOs\DataTransferObject;

final class GenerateReportDTO extends DataTransferObject
{
    public function __construct(
        public readonly int $reportDefinitionId,
        public readonly string $exportFormat,
        public readonly array $filters,
    ) {}

    public static function fromArray(array $data): static
    {
        return new self(
            reportDefinitionId: (int) ($data['report_definition_id'] ?? 0),
            exportFormat: (string) ($data['export_format'] ?? 'csv'),
            filters: (array) ($data['filters'] ?? []),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'report_definition_id' => $this->reportDefinitionId,
            'export_format'        => $this->exportFormat,
            'filters'              => $this->filters,
        ];
    }
}
