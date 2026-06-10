<?php

declare(strict_types=1);

namespace Modules\Payment\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Payment\Http\Requests\ChequePrintRequest;
use Modules\Payment\Http\Resources\ChequePrintLogResource;
use Modules\Payment\Models\Payment;
use Modules\Payment\Services\ChequePrintService;
use Modules\Payment\Services\ChequeTemplateService;

final class ChequePrintController
{
    public function preview(
        ChequePrintRequest $request,
        int $payment,
        ChequeTemplateService $templates,
        ChequePrintService $prints,
    ): JsonResponse {
        $row = $this->payment($request, $payment);
        $template = $templates->resolveActive(
            $request->tenantId(),
            $request->organizationUnitId(),
            $request->filled('cheque_template_id') ? (int) $request->input('cheque_template_id') : null,
        );

        return response()->json(['data' => $prints->preview($row, $template)]);
    }

    public function markPrinted(
        ChequePrintRequest $request,
        int $payment,
        ChequeTemplateService $templates,
        ChequePrintService $prints,
    ): JsonResponse {
        $row = $this->payment($request, $payment);
        $template = $templates->resolveActive(
            $request->tenantId(),
            $request->organizationUnitId(),
            $request->filled('cheque_template_id') ? (int) $request->input('cheque_template_id') : null,
        );
        $log = $prints->markPrinted(
            $row,
            $template,
            $request->currentUserId(),
            $request->filled('notes') ? (string) $request->input('notes') : null,
        );

        return (new ChequePrintLogResource($log))->response()->setStatusCode(201);
    }

    private function payment(ChequePrintRequest $request, int $payment): Payment
    {
        $query = Payment::query()
            ->where('tenant_id', $request->tenantId())
            ->with(['lines.paymentMethod', 'bankAccount']);

        $request->organizationUnitId() === null
            ? $query->whereNull('organization_unit_id')
            : $query->where('organization_unit_id', $request->organizationUnitId());

        return $query->findOrFail($payment);
    }
}
