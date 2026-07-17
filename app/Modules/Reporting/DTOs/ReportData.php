<?php

declare(strict_types=1);

namespace Modules\Reporting\DTOs;

use DateTimeImmutable;

final readonly class ReportData
{
    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<int, array{label: string, value: mixed, format: string}>  $summary
     * @param  array<string, mixed>  $branding
     * @param  array<int, array{label: string, value: string}>  $filters
     */
    public function __construct(
        public ReportDefinition $definition,
        public array $rows,
        public array $summary,
        public array $branding,
        public array $filters,
        public DateTimeImmutable $generatedAt,
        public string $template,
        public string $mode = 'preview',
        public ?int $rowLimit = null,
        public bool $truncated = false,
    ) {}

    public function orientation(): string
    {
        $orientation = $this->definition->orientation
            ?? (count($this->definition->columns) > 7
                ? 'landscape'
                : (string) config('reporting.pdf.orientation', 'portrait'));

        return $orientation === 'landscape' ? 'landscape' : 'portrait';
    }

    public function withMode(string $mode): self
    {
        return new self(
            $this->definition,
            $this->rows,
            $this->summary,
            $this->branding,
            $this->filters,
            $this->generatedAt,
            $this->template,
            $mode,
            $this->rowLimit,
            $this->truncated,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function viewData(): array
    {
        return [
            'report' => $this,
            'definition' => $this->definition,
            'rows' => $this->rows,
            'summary' => $this->summary,
            'branding' => $this->branding,
            'filters' => $this->filters,
            'generatedAt' => $this->generatedAt,
            'mode' => $this->mode,
            'orientation' => $this->orientation(),
            'rowLimit' => $this->rowLimit,
            'truncated' => $this->truncated,
        ];
    }
}
