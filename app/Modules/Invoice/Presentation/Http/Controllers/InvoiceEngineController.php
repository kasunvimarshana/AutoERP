<?php

declare(strict_types=1);

namespace Modules\Invoice\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Invoice\Application\Contracts\UseCases\InvoiceEngines\RecalculateInvoiceTotalsServiceInterface;
use Modules\Invoice\Application\Contracts\UseCases\InvoiceEngines\TransitionInvoiceStatusServiceInterface;
use Modules\Invoice\Presentation\Http\Requests\RecalculateInvoiceTotalsRequest;
use Modules\Invoice\Presentation\Http\Requests\TransitionInvoiceStatusRequest;

final class InvoiceEngineController extends Controller
{
    public function __construct(
        private readonly RecalculateInvoiceTotalsServiceInterface $recalculateInvoiceTotalsService,
        private readonly TransitionInvoiceStatusServiceInterface $transitionInvoiceStatusService,
    ) {
    }

    public function recalculateTotals(
        RecalculateInvoiceTotalsRequest $request,
        int|string $invoice,
    ): JsonResponse {
        $result = $this->recalculateInvoiceTotalsService->execute($invoice, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();

            return response()->json([
                'message' => $error->message,
                'code' => $error->code,
                'context' => $error->context,
            ], $error->code === 'INVOICE_NOT_FOUND' ? 404 : 422);
        }

        return response()->json(['data' => $result->valueOrFail()]);
    }

    public function transitionStatus(
        TransitionInvoiceStatusRequest $request,
        int|string $invoice,
    ): JsonResponse {
        $result = $this->transitionInvoiceStatusService->execute($invoice, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();

            return response()->json([
                'message' => $error->message,
                'code' => $error->code,
                'context' => $error->context,
            ], $error->code === 'INVOICE_NOT_FOUND' ? 404 : 422);
        }

        return response()->json(['data' => $result->valueOrFail()->toArray()]);
    }
}
