<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\VehicleRental\Enums\AgreementKind;
use Modules\VehicleRental\Http\Requests\BillingCycleRequest;
use Modules\VehicleRental\Models\BillingCycle;
use Modules\VehicleRental\Services\TwoStageInvoicingService;
use Symfony\Component\HttpFoundation\Response;

final class BillingCycleController
{
    public function __construct(private readonly TwoStageInvoicingService $invoicingService) {}

    public function index(BillingCycleRequest $request): JsonResponse
    {
        $context = $request->context();
        $agreementId = $request->query('agreement_id');
        $kind = $request->query('kind');

        $query = BillingCycle::query()
            ->forContext($context->tenantId, $context->organizationUnitId)
            ->with(['initialInvoice', 'finalInvoice', 'supplementaryInvoice', 'customerAgreement', 'ownerAgreement'])
            ->orderByDesc('id');

        if ($agreementId !== null) {
            if ($kind === 'owner') {
                $query->where('owner_agreement_id', (int) $agreementId);
            } else {
                $query->where('customer_agreement_id', (int) $agreementId);
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
                'items',
                'initialInvoice',
                'finalInvoice',
                'supplementaryInvoice',
                'customerAgreement.customer',
                'ownerAgreement.supplier',
            ])
            ->findOrFail($cycle);

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
}
