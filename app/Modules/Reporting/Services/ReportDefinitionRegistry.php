<?php

declare(strict_types=1);

namespace Modules\Reporting\Services;

use InvalidArgumentException;
use Modules\Reporting\DTOs\ReportDefinition;

final class ReportDefinitionRegistry
{
    public function __construct(
        private readonly ReportCatalog $catalog,
        private readonly DetailedPurchaseReportService $detailedPurchase,
        private readonly DetailedVehicleServiceReportService $detailedVehicleService,
    ) {}

    /**
     * @return array<string, ReportDefinition>
     */
    public function all(): array
    {
        $reports = $this->catalog->all();

        foreach ($this->specializedDefinitions() as $definition) {
            $reports[$definition->key] = $definition;
        }

        return $reports;
    }

    public function get(string $key): ReportDefinition
    {
        return $this->all()[$key] ?? throw new InvalidArgumentException("Report [{$key}] is not defined.");
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function index(): array
    {
        return array_values(array_map(
            static fn (ReportDefinition $definition): array => $definition->toArray(),
            $this->all(),
        ));
    }

    /**
     * @return array<int, ReportDefinition>
     */
    private function specializedDefinitions(): array
    {
        return [
            $this->detailedPurchase->definition(),
            $this->detailedVehicleService->definition(),
        ];
    }
}
