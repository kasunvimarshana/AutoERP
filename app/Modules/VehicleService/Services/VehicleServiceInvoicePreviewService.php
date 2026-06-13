<?php

declare(strict_types=1);

namespace Modules\VehicleService\Services;

use Modules\Invoice\DTOs\InvoiceCalculationResult;
use Modules\Invoice\Services\InvoiceCreationService;
use Modules\VehicleService\Models\VehicleServiceJob;

final class VehicleServiceInvoicePreviewService
{
    public function __construct(
        private readonly InvoiceCreationService $invoices,
        private readonly VehicleServiceInvoiceSourceMapper $sources,
    ) {}

    /** @param array<int, string> $lineQuantities */
    public function preview(
        VehicleServiceJob $job,
        string $invoiceDate,
        array $lineQuantities = [],
    ): InvoiceCalculationResult {
        return $this->invoices->preview(
            $this->sources->toInvoiceData($job, $invoiceDate, $lineQuantities),
        );
    }
}
