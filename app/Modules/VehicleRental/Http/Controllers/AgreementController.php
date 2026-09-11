<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\VehicleRental\Constants\AgreementFields;
use Modules\VehicleRental\Enums\AgreementAction;
use Modules\VehicleRental\Enums\AgreementKind;
use Modules\VehicleRental\Http\Requests\AgreementRequest;
use Modules\VehicleRental\Http\Resources\AgreementHistoryResource;
use Modules\VehicleRental\Http\Resources\AgreementResource;
use Modules\VehicleRental\Services\AgreementService;
use Modules\VehicleRental\Services\BaseRentPreview;
use Symfony\Component\HttpFoundation\Response;

final class AgreementController
{
    public function __construct(private readonly AgreementService $agreements) {}

    public function index(AgreementRequest $request, string $kind): AnonymousResourceCollection
    {
        return AgreementResource::collection($this->agreements->list(AgreementKind::from($kind), $request->context(), $request->perPage(), $request->validated('search')));
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
}
