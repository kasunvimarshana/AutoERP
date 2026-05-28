<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLessorAgreements\CreateVehicleRentalLessorAgreementServiceInterface;
use Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLessorAgreements\DeleteVehicleRentalLessorAgreementServiceInterface;
use Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLessorAgreements\GetVehicleRentalLessorAgreementServiceInterface;
use Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLessorAgreements\ListVehicleRentalLessorAgreementsServiceInterface;
use Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLessorAgreements\UpdateVehicleRentalLessorAgreementServiceInterface;
use Modules\VehicleRental\Presentation\Http\Requests\ListVehicleRentalLessorAgreementRequest;
use Modules\VehicleRental\Presentation\Http\Requests\UpsertVehicleRentalLessorAgreementRequest;
use Modules\VehicleRental\Presentation\Http\Resources\VehicleRentalLessorAgreementResource;

final class VehicleRentalLessorAgreementController extends Controller
{
    public function __construct(
        private readonly ListVehicleRentalLessorAgreementsServiceInterface $listService,
        private readonly GetVehicleRentalLessorAgreementServiceInterface $getService,
        private readonly CreateVehicleRentalLessorAgreementServiceInterface $createService,
        private readonly UpdateVehicleRentalLessorAgreementServiceInterface $updateService,
        private readonly DeleteVehicleRentalLessorAgreementServiceInterface $deleteService,
    ) {
    }

    public function index(ListVehicleRentalLessorAgreementRequest $request): JsonResponse
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
            'data' => VehicleRentalLessorAgreementResource::collection($pageResult->items)->resolve(),
            'meta' => [
                'total' => $pageResult->total,
                'page' => $pageResult->page,
                'per_page' => $pageResult->perPage,
                'page_count' => $pageResult->pageCount(),
                'has_more' => $pageResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|VehicleRentalLessorAgreementResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new VehicleRentalLessorAgreementResource($result->valueOrFail());
    }

    public function store(UpsertVehicleRentalLessorAgreementRequest $request): JsonResponse|VehicleRentalLessorAgreementResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new VehicleRentalLessorAgreementResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertVehicleRentalLessorAgreementRequest $request, int|string $id): JsonResponse|VehicleRentalLessorAgreementResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'VEHICLERENTAL_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new VehicleRentalLessorAgreementResource($result->valueOrFail());
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
