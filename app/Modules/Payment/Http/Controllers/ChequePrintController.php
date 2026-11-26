<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

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
use Spatie\LaravelPdf\Facades\Pdf;
use Symfony\Component\HttpFoundation\Response;

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

    public function export(
        ChequePrintRequest $request,
        int $payment,
        int $line,
        string $format,
        ChequeTemplateService $templates,
        ChequePrintService $prints,
    ): Response {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PaymentPermission::CHEQUES_PREVIEW);
        [$row, $paymentLine] = $this->paymentLine($request, $payment, $line);
        $template = $templates->resolveActive(
            $request->tenantId(),
            $request->organizationUnitId(),
            $request->filled('cheque_template_id') ? (int) $request->input('cheque_template_id') : null,
        );

        $previewData = $prints->preview($row, $paymentLine, $template);
        $viewName = 'payment.cheque-print';
        $chequeNumber = $previewData['line']['cheque_number'] ?? 'preview';
        $filename = "cheque-{$previewData['payment']['payment_number']}-{$chequeNumber}.pdf";

        if ($format === 'pdf') {
            return Pdf::view($viewName, array_merge($previewData, ['mode' => 'pdf']))
                ->paperSize(
                    (float) $template->page_width_mm,
                    (float) $template->page_height_mm,
                    'mm'
                )
                ->margins(0, 0, 0, 0)
                ->name($filename)
                ->download()
                ->toResponse($request);
        }

        return response()->view(
            $viewName,
            array_merge($previewData, ['mode' => $format === 'print' ? 'print' : 'html']),
            200,
            ['Content-Type' => 'text/html; charset=UTF-8'],
        );
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
