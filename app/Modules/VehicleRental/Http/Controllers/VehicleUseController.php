<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\VehicleRental\Constants\OperationalFields;
use Modules\VehicleRental\Enums\AgreementStatus;
use Modules\VehicleRental\Enums\VehicleUseAction;
use Modules\VehicleRental\Http\Requests\AgreementRequest;
use Modules\VehicleRental\Http\Resources\OwnerSourceResource;
use Modules\VehicleRental\Http\Resources\VehicleUseHistoryResource;
use Modules\VehicleRental\Http\Resources\VehicleUseResource;
use Modules\VehicleRental\Models\OwnerAgreement;
use Modules\VehicleRental\Services\RentalAuthorization;
use Modules\VehicleRental\Services\VehicleUseRegisterService;
use Modules\VehicleRental\Services\VehicleUseService;
use Symfony\Component\HttpFoundation\Response;

final class VehicleUseController
{
    public function __construct(private readonly VehicleUseService $uses, private readonly RentalAuthorization $authorization) {}

    public function register(AgreementRequest $request, VehicleUseRegisterService $register): AnonymousResourceCollection
    {
        return VehicleUseResource::collection($register->list($request->context(), $request->only(['search', 'use_status', 'from', 'until']), $request->perPage()));
    }

    public function index(AgreementRequest $request, int $agreement): AnonymousResourceCollection
    {
        return VehicleUseResource::collection($this->uses->list($request->context(), $agreement, $request->perPage()));
    }

    public function store(AgreementRequest $request, int $agreement): JsonResponse
    {
        return (new VehicleUseResource($this->uses->plan($request->context(), $agreement, $request->expectedVersion(), $request->only(OperationalFields::MUTABLE_USE))))->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function transition(AgreementRequest $request, int $use, string $action): VehicleUseResource
    {
        return new VehicleUseResource($this->uses->transition($request->context(), $use, $request->expectedVersion(), VehicleUseAction::from($action), $request->only(['occurred_at', 'odometer', 'reason'])));
    }

    public function replace(AgreementRequest $request, int $use): VehicleUseResource
    {
        return new VehicleUseResource($this->uses->replace($request->context(), $use, $request->expectedVersion(), $request->only(array_merge(OperationalFields::MUTABLE_USE, ['reason', 'return_odometer', 'handover_odometer']))));
    }

    public function history(AgreementRequest $request, int $use): AnonymousResourceCollection
    {
        return VehicleUseHistoryResource::collection($this->uses->find($request->context(), $use)->history()->with('actor')->paginate($request->perPage()));
    }

    public function sources(AgreementRequest $request, int $vehicle): AnonymousResourceCollection
    {
        $context = $request->context();
        $this->authorization->assertUse($context, false);

        return OwnerSourceResource::collection(OwnerAgreement::query()->forContext($context->tenantId, $context->organizationUnitId)
            ->where('vehicle_id', $vehicle)->where('status', AgreementStatus::Active->value)
            ->when($request->validated('search'), fn ($q, $search) => $q->where(fn ($terms) => $terms->where('reference', 'like', '%'.$search.'%')->orWhere('party_name_snapshot', 'like', '%'.$search.'%')))
            ->orderBy('reference')->paginate($request->perPage()));
    }
}
