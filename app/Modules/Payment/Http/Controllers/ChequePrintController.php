<?php

declare(strict_types=1);

namespace Modules\Payment\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Payment\Constants\PaymentPermission;
use Modules\Payment\Http\Requests\ChequePrintRequest;
use Modules\Payment\Http\Resources\ChequePrintLogResource;
use Modules\Payment\Models\Payment;
use Modules\Payment\Models\PaymentLine;
use Modules\Payment\Services\ChequePrintService;
use Modules\Payment\Services\ChequeTemplateService;
use Modules\Payment\Services\PaymentAuthorizationService;

final class ChequePrintController
{
    public function __construct(private readonly PaymentAuthorizationService $authorization) {}

    public function preview(
        ChequePrintRequest $request,
        int $payment,
        int $line,
        ChequeTemplateService $templates,
        ChequePrintService $prints,
    ): JsonResponse {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PaymentPermission::CHEQUES_PREVIEW);
        [$row, $paymentLine] = $this->paymentLine($request, $payment, $line);
        $template = $templates->resolveActive(
            $request->tenantId(),
            $request->organizationUnitId(),
            $request->filled('cheque_template_id') ? (int) $request->input('cheque_template_id') : null,
        );

        return response()->json(['data' => $prints->preview($row, $paymentLine, $template)]);
    }

    public function markPrinted(
        ChequePrintRequest $request,
        int $payment,
        int $line,
        ChequeTemplateService $templates,
        ChequePrintService $prints,
    ): JsonResponse {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PaymentPermission::CHEQUES_PRINT);
        [$row, $paymentLine] = $this->paymentLine($request, $payment, $line);
        $template = $templates->resolveActive(
            $request->tenantId(),
            $request->organizationUnitId(),
            $request->filled('cheque_template_id') ? (int) $request->input('cheque_template_id') : null,
        );
        $log = $prints->markPrinted(
            $row,
            $paymentLine,
            $template,
            $request->currentUserId(),
            $request->filled('notes') ? (string) $request->input('notes') : null,
        );

        return (new ChequePrintLogResource($log))->response()->setStatusCode(201);
    }

    /** @return array{0: Payment, 1: PaymentLine} */
    private function paymentLine(ChequePrintRequest $request, int $payment, int $line): array
    {
        $query = Payment::query()
            ->where('tenant_id', $request->tenantId())
            ->with(['lines.paymentMethod']);

        $request->organizationUnitId() === null
            ? $query->whereNull('organization_unit_id')
            : $query->where('organization_unit_id', $request->organizationUnitId());

        $row = $query->findOrFail($payment);
        $paymentLine = $row->lines->firstWhere('id', $line);
        if (! $paymentLine instanceof PaymentLine) {
            abort(404);
        }

        return [$row, $paymentLine];
    }
}
