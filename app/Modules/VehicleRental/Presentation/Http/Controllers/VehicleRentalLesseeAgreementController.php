<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLesseeAgreements\CreateVehicleRentalLesseeAgreementServiceInterface;
use Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLesseeAgreements\DeleteVehicleRentalLesseeAgreementServiceInterface;
use Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLesseeAgreements\GetVehicleRentalLesseeAgreementServiceInterface;
use Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLesseeAgreements\ListVehicleRentalLesseeAgreementsServiceInterface;
use Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLesseeAgreements\UpdateVehicleRentalLesseeAgreementServiceInterface;
use Modules\VehicleRental\Presentation\Http\Requests\ListVehicleRentalLesseeAgreementRequest;
use Modules\VehicleRental\Presentation\Http\Requests\UpsertVehicleRentalLesseeAgreementRequest;
use Modules\VehicleRental\Presentation\Http\Resources\VehicleRentalLesseeAgreementResource;

final class VehicleRentalLesseeAgreementController extends Controller
{
    public function __construct(
        private readonly ListVehicleRentalLesseeAgreementsServiceInterface $listService,
        private readonly GetVehicleRentalLesseeAgreementServiceInterface $getService,
        private readonly CreateVehicleRentalLesseeAgreementServiceInterface $createService,
        private readonly UpdateVehicleRentalLesseeAgreementServiceInterface $updateService,
        private readonly DeleteVehicleRentalLesseeAgreementServiceInterface $deleteService,
    ) {
    }

    public function index(ListVehicleRentalLesseeAgreementRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $perPage = (int) ($validated['per_page'] ?? 0);
        $page = (int) ($validated['page'] ?? 0);
        unset($validated['per_page'], $validated['page']);

        $result = $this->listService->execute($validated, $perPage, $page);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        $pageResult = $result->valueOrFail();
        if (! $pageResult instanceof PagedResult) {
            return response()->json(['message' => 'Unexpected list response.'], 500);
        }

        return response()->json([
            'data' => VehicleRentalLesseeAgreementResource::collection($pageResult->items)->resolve(),
            'meta' => [
                'total' => $pageResult->total,
                'page' => $pageResult->page,
                'per_page' => $pageResult->perPage,
                'page_count' => $pageResult->pageCount(),
                'has_more' => $pageResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|VehicleRentalLesseeAgreementResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new VehicleRentalLesseeAgreementResource($result->valueOrFail());
    }

    public function store(UpsertVehicleRentalLesseeAgreementRequest $request): JsonResponse|VehicleRentalLesseeAgreementResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new VehicleRentalLesseeAgreementResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertVehicleRentalLesseeAgreementRequest $request, int|string $id): JsonResponse|VehicleRentalLesseeAgreementResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'VEHICLERENTAL_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new VehicleRentalLesseeAgreementResource($result->valueOrFail());
    }

    public function destroy(int|string $id): JsonResponse
    {
        $result = $this->deleteService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return response()->json(null, 204);
    }
}
