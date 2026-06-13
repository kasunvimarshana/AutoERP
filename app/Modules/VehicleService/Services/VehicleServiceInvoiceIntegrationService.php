<?php

declare(strict_types=1);

namespace Modules\VehicleService\Services;

use Illuminate\Support\Collection;
use Modules\Invoice\DTOs\InvoiceCalculationResult;
use Modules\Invoice\Models\Invoice;
use Modules\VehicleService\Models\VehicleServiceJob;
use Modules\VehicleService\Models\VehicleServiceJobLine;

final class VehicleServiceInvoiceIntegrationService
{
    public function __construct(
        private readonly VehicleServiceInvoiceSourceMapper $sources,
        private readonly VehicleServiceInvoicePreviewService $previews,
        private readonly VehicleServiceInvoiceCreator $creator,
    ) {}

    /** @return Collection<int, VehicleServiceJobLine> */
    public function billableLines(VehicleServiceJob $job): Collection
    {
        return $this->sources->billableLines($job);
    }

    /** @param array<int, string> $lineQuantities */
    public function preview(
        VehicleServiceJob $job,
        string $invoiceDate,
        array $lineQuantities = [],
    ): InvoiceCalculationResult {
        return $this->previews->preview($job, $invoiceDate, $lineQuantities);
    }

    /** @param array<int, string> $lineQuantities */
    public function create(
        VehicleServiceJob $job,
        string $invoiceDate,
        array $lineQuantities = [],
        ?string $dueDate = null,
        ?int $currencyId = null,
        string $exchangeRate = '1.000000',
        ?string $notes = null,
        ?int $createdBy = null,
    ): Invoice {
        return $this->creator->create(
            $job,
            $invoiceDate,
            $lineQuantities,
            $dueDate,
            $currencyId,
            $exchangeRate,
            $notes,
            $createdBy,
        );
    }
}
