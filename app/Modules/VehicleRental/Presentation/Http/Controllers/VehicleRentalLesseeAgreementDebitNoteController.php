<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLesseeAgreementDebitNotes\CreateVehicleRentalLesseeAgreementDebitNoteServiceInterface;
use Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLesseeAgreementDebitNotes\DeleteVehicleRentalLesseeAgreementDebitNoteServiceInterface;
use Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLesseeAgreementDebitNotes\GetVehicleRentalLesseeAgreementDebitNoteServiceInterface;
use Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLesseeAgreementDebitNotes\ListVehicleRentalLesseeAgreementDebitNotesServiceInterface;
use Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLesseeAgreementDebitNotes\UpdateVehicleRentalLesseeAgreementDebitNoteServiceInterface;
use Modules\VehicleRental\Presentation\Http\Requests\ListVehicleRentalLesseeAgreementDebitNoteRequest;
use Modules\VehicleRental\Presentation\Http\Requests\UpsertVehicleRentalLesseeAgreementDebitNoteRequest;
use Modules\VehicleRental\Presentation\Http\Resources\VehicleRentalLesseeAgreementDebitNoteResource;

final class VehicleRentalLesseeAgreementDebitNoteController extends Controller
{
    public function __construct(
        private readonly ListVehicleRentalLesseeAgreementDebitNotesServiceInterface $listService,
        private readonly GetVehicleRentalLesseeAgreementDebitNoteServiceInterface $getService,
        private readonly CreateVehicleRentalLesseeAgreementDebitNoteServiceInterface $createService,
        private readonly UpdateVehicleRentalLesseeAgreementDebitNoteServiceInterface $updateService,
        private readonly DeleteVehicleRentalLesseeAgreementDebitNoteServiceInterface $deleteService,
    ) {
    }

    public function index(ListVehicleRentalLesseeAgreementDebitNoteRequest $request): JsonResponse
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
            'data' => VehicleRentalLesseeAgreementDebitNoteResource::collection($pageResult->items)->resolve(),
            'meta' => [
                'total' => $pageResult->total,
                'page' => $pageResult->page,
                'per_page' => $pageResult->perPage,
                'page_count' => $pageResult->pageCount(),
                'has_more' => $pageResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|VehicleRentalLesseeAgreementDebitNoteResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new VehicleRentalLesseeAgreementDebitNoteResource($result->valueOrFail());
    }

    public function store(UpsertVehicleRentalLesseeAgreementDebitNoteRequest $request): JsonResponse|VehicleRentalLesseeAgreementDebitNoteResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new VehicleRentalLesseeAgreementDebitNoteResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertVehicleRentalLesseeAgreementDebitNoteRequest $request, int|string $id): JsonResponse|VehicleRentalLesseeAgreementDebitNoteResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'VEHICLERENTAL_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new VehicleRentalLesseeAgreementDebitNoteResource($result->valueOrFail());
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
