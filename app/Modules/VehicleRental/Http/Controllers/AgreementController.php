<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Modules\Invoice\Http\Resources\InvoiceResource;
use Modules\VehicleRental\Constants\AgreementFields;
use Modules\VehicleRental\Enums\AgreementAction;
use Modules\VehicleRental\Enums\AgreementKind;
use Modules\VehicleRental\Enums\AgreementStatus;
use Modules\VehicleRental\Http\Requests\AgreementRequest;
use Modules\VehicleRental\Http\Resources\AgreementHistoryResource;
use Modules\VehicleRental\Http\Resources\AgreementResource;
use Modules\VehicleRental\Services\AgreementFinancialSummaryService;
use Modules\VehicleRental\Services\AgreementService;
use Modules\VehicleRental\Services\BaseRentPreview;
use Modules\VehicleRental\Services\RentalChargeDocuments;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

final class AgreementController
{
    public function __construct(private readonly AgreementService $agreements) {}

    public function destroy(AgreementRequest $request, string $kind, int $agreement): JsonResponse
    {
        throw new MethodNotAllowedHttpException([], 'Rental agreements cannot be deleted to preserve legal and financial audit history. Close the agreement instead.');
    }

    public function index(AgreementRequest $request, string $kind): AnonymousResourceCollection
    {
        $status = $request->validated('status') !== null ? AgreementStatus::tryFrom((string) $request->validated('status')) : null;

        return AgreementResource::collection($this->agreements->list(AgreementKind::from($kind), $request->context(), $request->perPage(), $request->validated('search'), $status));
    }

    public function store(AgreementRequest $request, string $kind): JsonResponse
    {
        return (new AgreementResource($this->agreements->create(AgreementKind::from($kind), $request->context(), $request->only(AgreementFields::MUTABLE))))->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(AgreementRequest $request, string $kind, int $agreement): AgreementResource
    {
        return new AgreementResource($this->agreements->find(AgreementKind::from($kind), $request->context(), $agreement));
    }

    public function update(AgreementRequest $request, string $kind, int $agreement): AgreementResource
    {
        return new AgreementResource($this->agreements->change(AgreementKind::from($kind), $request->context(), $agreement, $request->expectedVersion(), AgreementAction::Update, $request->only(AgreementFields::MUTABLE)));
    }

    public function successor(AgreementRequest $request, string $kind, int $agreement): JsonResponse
    {
        $successor = $this->agreements->successor(AgreementKind::from($kind), $request->context(), $agreement, $request->expectedVersion(), $request->only(['reference', 'agreed_on', 'executing_on', 'starts_on', 'ends_on', 'reason']));

        return (new AgreementResource($successor))->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function transition(AgreementRequest $request, string $kind, int $agreement, string $action): AgreementResource
    {
        return new AgreementResource($this->agreements->change(AgreementKind::from($kind), $request->context(), $agreement, $request->expectedVersion(), AgreementAction::from($action), reason: $request->validated('reason')));
    }

    public function history(AgreementRequest $request, string $kind, int $agreement): AnonymousResourceCollection
    {
        return AgreementHistoryResource::collection($this->agreements->find(AgreementKind::from($kind), $request->context(), $agreement)->history()->with('actor')->paginate($request->perPage()));
    }

    public function previewBaseRent(AgreementRequest $request, string $kind, int $agreement, BaseRentPreview $preview): JsonResponse
    {
        return response()->json(['data' => $preview->calculate(AgreementKind::from($kind), $request->context(), $agreement, $request->only(['policy', 'expected_version', 'from', 'until']))]);
    }

    public function financialSummary(AgreementRequest $request, string $kind, int $agreement, AgreementFinancialSummaryService $service): JsonResponse
    {
        return response()->json([
            'data' => $service->generate(AgreementKind::from($kind), $request->context(), $agreement),
        ]);
    }

    public function createFinancialAdjustment(AgreementRequest $request, string $kind, int $agreement, RentalChargeDocuments $documents): JsonResponse
    {
        $context = $request->context();
        $agreementKind = AgreementKind::from($kind);
        $data = $documents->adjustmentInput($request->all());

        return DB::transaction(function () use ($agreementKind, $context, $agreement, $data, $documents): JsonResponse {
            $targetAgreement = $this->agreements->find($agreementKind, $context, $agreement);
            $lockedAgreement = $targetAgreement->newQuery()->forContext($context->tenantId, $context->organizationUnitId)->lockForUpdate()->findOrFail($targetAgreement->id);
            $documents->assertAgreement($lockedAgreement, (int) $data['expected_version']);

            $invoice = $documents->issueAdjustment(
                $agreementKind,
                $context,
                $lockedAgreement,
                $data['adjustment_type'],
                $data['amount'],
                $data['reason'],
                $data,
            );

            return (new InvoiceResource($invoice))->response()->setStatusCode(Response::HTTP_CREATED);
        });
    }
}
