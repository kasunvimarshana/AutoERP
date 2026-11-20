<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Controllers;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Modules\VehicleRental\Enums\AgreementKind;
use Modules\VehicleRental\Http\Requests\BillingCycleRequest;
use Modules\VehicleRental\Models\BillingCycle;
use Modules\VehicleRental\Services\RentalAuthorization;
use Modules\VehicleRental\Services\TwoStageInvoicingService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

final class BillingCycleController
{
    public function __construct(
        private readonly TwoStageInvoicingService $invoicingService,
        private readonly RentalAuthorization $authorization,
    ) {}

    public function destroy(): JsonResponse
    {
        throw new MethodNotAllowedHttpException([], 'Billing cycles cannot be deleted to preserve financial audit history. Cancel the cycle instead.');
    }

    public function cancel(BillingCycleRequest $request, int $cycle): JsonResponse
    {
        $record = $this->invoicingService->cancelCycle(
            $request->context(),
            $cycle,
            $request->expectedVersion(),
            $request->input('reason') ? (string) $request->input('reason') : null,
        );

        return response()->json(['data' => $record]);
    }

    public function index(BillingCycleRequest $request): JsonResponse
    {
        $context = $request->context();
        $agreementId = $request->query('agreement_id');
        $kind = $request->query('kind');

        $query = BillingCycle::query()
            ->forContext($context->tenantId, $context->organizationUnitId)
            ->with(['initialInvoice', 'finalInvoice', 'supplementaryInvoice', 'customerAgreement', 'ownerAgreement'])
            ->orderByDesc('id');

        if ($kind === 'owner') {
            $this->authorization->assertCycleView($context, AgreementKind::Owner);
            $query->whereNotNull('owner_agreement_id');
        } elseif ($kind === 'customer') {
            $this->authorization->assertCycleView($context, AgreementKind::Customer);
            $query->whereNotNull('customer_agreement_id');
        } else {
            $canCust = $this->authorization->canViewOrBillCycle($context, AgreementKind::Customer);
            $canOwn = $this->authorization->canViewOrBillCycle($context, AgreementKind::Owner);

            if (! $canCust && ! $canOwn) {
                throw new AuthorizationException('This Rental action requires agreement view or billing permission.');
            }

            if ($canCust && ! $canOwn) {
                $query->whereNotNull('customer_agreement_id');
            } elseif (! $canCust && $canOwn) {
                $query->whereNotNull('owner_agreement_id');
            }
        }

        if ($agreementId !== null) {
            if ($kind === 'owner') {
                $query->where('owner_agreement_id', (int) $agreementId);
            } elseif ($kind === 'customer') {
                $query->where('customer_agreement_id', (int) $agreementId);
            } else {
                $query->where(function ($q) use ($agreementId): void {
                    $q->where('customer_agreement_id', (int) $agreementId)
                        ->orWhere('owner_agreement_id', (int) $agreementId);
                });
            }
        }

        $paginated = $query->paginate($request->perPage());

        return response()->json([
            'data' => $paginated->items(),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    public function store(BillingCycleRequest $request, string $kind, int $agreement): JsonResponse
    {
        $cycle = $this->invoicingService->createCycle(
            AgreementKind::from($kind),
            $request->context(),
            $agreement,
            $request->all()
        );

        return response()->json(['data' => $cycle], Response::HTTP_CREATED);
    }

    public function show(BillingCycleRequest $request, int $cycle): JsonResponse
    {
        $context = $request->context();
        $record = BillingCycle::query()
            ->forContext($context->tenantId, $context->organizationUnitId)
            ->with([
                'charts.runningChart',
                'charts.vehicle',
                'items.vehicle',
                'items.driver',
                'initialInvoice',
                'finalInvoice',
                'supplementaryInvoice',
                'customerAgreement.customer',
                'ownerAgreement.supplier',
            ])
            ->findOrFail($cycle);

        $kind = $record->customer_agreement_id !== null ? AgreementKind::Customer : AgreementKind::Owner;
        $this->authorization->assertCycleView($context, $kind);

        return response()->json(['data' => $record]);
    }

    public function attachCharts(BillingCycleRequest $request, int $cycle): JsonResponse
    {
        $chartIds = (array) $request->input('chart_ids', []);
        $record = $this->invoicingService->attachRunningCharts(
            $request->context(),
            $cycle,
            $chartIds,
            $request->expectedVersion()
        );

        return response()->json(['data' => $record]);
    }

    public function addItem(BillingCycleRequest $request, int $cycle): JsonResponse
    {
        $item = $this->invoicingService->addItem(
            $request->context(),
            $cycle,
            $request->all(),
            $request->expectedVersion()
        );

        return response()->json(['data' => $item], Response::HTTP_CREATED);
    }

    public function removeItem(BillingCycleRequest $request, int $cycle, int $item): JsonResponse
    {
        $this->invoicingService->removeItem(
            $request->context(),
            $cycle,
            $item,
            $request->expectedVersion()
        );

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    public function preview(BillingCycleRequest $request, int $cycle): JsonResponse
    {
        $preview = $this->invoicingService->previewFinalStage(
            $request->context(),
            $cycle
        );

        return response()->json(['data' => $preview]);
    }

    public function finalize(BillingCycleRequest $request, int $cycle): JsonResponse
    {
        $record = $this->invoicingService->finalize(
            $request->context(),
            $cycle,
            $request->all(),
            $request->expectedVersion()
        );

        return response()->json(['data' => $record]);
    }

    public function eligibleCharts(BillingCycleRequest $request, int $cycle): JsonResponse
    {
        $charts = $this->invoicingService->getCandidateRunningCharts(
            $request->context(),
            $cycle
        );

        return response()->json(['data' => $charts]);
    }

    public function export(BillingCycleRequest $request, int $cycle, string $format): Response
    {
        $data = $this->invoicingService->getCycleStatementData(
            $request->context(),
            $cycle
        );

        $viewName = 'reports.vehicle-rental.billing-cycle-statement';
        $filename = "billing-cycle-statement-{$data['header']['cycle_number']}.pdf";

        if ($format === 'pdf') {
            return \Spatie\LaravelPdf\Facades\Pdf::view($viewName, array_merge($data, ['mode' => 'pdf']))
                ->format('A4')
                ->orientation('portrait')
                ->margins(8, 10, 8, 10)
                ->name($filename)
                ->download()
                ->toResponse($request);
        }

        if ($format === 'print') {
            return response()->view(
                $viewName,
                array_merge($data, ['mode' => 'print']),
                200,
                ['Content-Type' => 'text/html; charset=UTF-8'],
            );
        }

        return response()->view(
            $viewName,
            array_merge($data, ['mode' => 'html']),
            200,
            ['Content-Type' => 'text/html; charset=UTF-8'],
        );
    }
}
