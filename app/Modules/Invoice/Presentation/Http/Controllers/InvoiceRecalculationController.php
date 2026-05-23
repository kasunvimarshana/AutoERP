<?php

declare(strict_types=1);

namespace Modules\Invoice\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Invoice\Application\Services\InvoiceService;
use Modules\Invoice\Domain\Exceptions\InvoiceRecordNotFoundException;
use Modules\Invoice\Presentation\Http\Resources\InvoiceRecordResource;

class InvoiceRecalculationController extends Controller
{
    public function __construct(private readonly InvoiceService $invoices) {}

    public function invoice(int|string $tenant, int|string $invoice): InvoiceRecordResource|JsonResponse
    {
        try {
            return new InvoiceRecordResource($this->invoices->recalculateInvoice($tenant, $invoice));
        } catch (InvoiceRecordNotFoundException $exception) {
            return response()->json(['message' => $exception->getMessage()], 404);
        }
    }

    public function reference(int|string $tenant, int|string $reference): InvoiceRecordResource|JsonResponse
    {
        try {
            return new InvoiceRecordResource($this->invoices->recalculateReference($tenant, $reference));
        } catch (InvoiceRecordNotFoundException $exception) {
            return response()->json(['message' => $exception->getMessage()], 404);
        }
    }
}
