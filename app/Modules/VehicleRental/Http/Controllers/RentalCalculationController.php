<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\VehicleRental\Http\Requests\CancelRentalCalculationRequest;
use Modules\VehicleRental\Http\Requests\CreateRentalCalculationRequest;
use Modules\VehicleRental\Http\Requests\ListRentalRequest;
use Modules\VehicleRental\Http\Resources\RentalCalculationResource;
use Modules\VehicleRental\Models\RentalCalculation;
use Modules\VehicleRental\Services\RentalCalculationService;
use Modules\VehicleRental\Services\RentalFinancialDocumentService;

final class RentalCalculationController extends RentalController
{
    public function index(
        ListRentalRequest $request,
        RentalCalculationService $service,
        RentalFinancialDocumentService $financialDocuments,
    ): AnonymousResourceCollection {
        $query = RentalCalculation::query()
            ->forContext($request->tenantId(), $request->organizationUnitId())
            ->with($service->relations())
            ->orderByDesc('period_start')
            ->orderByDesc('id');
        if ($request->filled('agreement_id')) {
            $query->where('agreement_id', $request->validated('agreement_id'));
        }
        if ($request->filled('calculation_side')) {
            $query->where('side', $request->validated('calculation_side'));
        }
        if ($request->filled('calculation_status')) {
            $query->where('status', $request->validated('calculation_status'));
        }
        if ($request->boolean('has_financial_document') || $request->boolean('outstanding_only')) {
            $financialDocuments->constrainToActiveFinancialDocuments(
                $query,
                $request->boolean('outstanding_only'),
            );
        }
        if ($request->filled('date_from')) {
            $query->whereDate('period_end', '>=', $request->validated('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('period_start', '<=', $request->validated('date_to'));
        }

        $paginator = $query->paginate($request->perPage());
        $financialDocuments->attachFinancialDocuments($paginator->getCollection());

        return RentalCalculationResource::collection($paginator);
    }

    public function show(
        ListRentalRequest $request,
        int $calculation,
        RentalCalculationService $service,
        RentalFinancialDocumentService $financialDocuments,
    ): RentalCalculationResource {
        $record = $this->calculation($request, $calculation)->load($service->relations());
        $financialDocuments->attachFinancialDocuments(collect([$record]));

        return new RentalCalculationResource($record);
    }

    public function calculate(
        CreateRentalCalculationRequest $request,
        int $agreement,
        RentalCalculationService $service,
    ): JsonResponse {
        return (new RentalCalculationResource($service->calculate(
            $this->agreement($request, $agreement),
            $request->toData(),
        )))->response()->setStatusCode(201);
    }

    public function cancel(
        CancelRentalCalculationRequest $request,
        int $calculation,
        RentalCalculationService $service,
    ): RentalCalculationResource {
        return new RentalCalculationResource($service->cancel(
            $this->calculation($request, $calculation),
            $request->expectedVersion(),
            $request->reason(),
            $request->currentUserId(),
        ));
    }
}
