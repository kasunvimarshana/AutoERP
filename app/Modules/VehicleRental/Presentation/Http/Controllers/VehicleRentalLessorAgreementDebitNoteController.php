<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLessorAgreementDebitNotes\CreateVehicleRentalLessorAgreementDebitNoteServiceInterface;
use Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLessorAgreementDebitNotes\DeleteVehicleRentalLessorAgreementDebitNoteServiceInterface;
use Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLessorAgreementDebitNotes\GetVehicleRentalLessorAgreementDebitNoteServiceInterface;
use Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLessorAgreementDebitNotes\ListVehicleRentalLessorAgreementDebitNotesServiceInterface;
use Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLessorAgreementDebitNotes\UpdateVehicleRentalLessorAgreementDebitNoteServiceInterface;
use Modules\VehicleRental\Presentation\Http\Requests\ListVehicleRentalLessorAgreementDebitNoteRequest;
use Modules\VehicleRental\Presentation\Http\Requests\UpsertVehicleRentalLessorAgreementDebitNoteRequest;
use Modules\VehicleRental\Presentation\Http\Resources\VehicleRentalLessorAgreementDebitNoteResource;

final class VehicleRentalLessorAgreementDebitNoteController extends Controller
{
    public function __construct(
        private readonly ListVehicleRentalLessorAgreementDebitNotesServiceInterface $listService,
        private readonly GetVehicleRentalLessorAgreementDebitNoteServiceInterface $getService,
        private readonly CreateVehicleRentalLessorAgreementDebitNoteServiceInterface $createService,
        private readonly UpdateVehicleRentalLessorAgreementDebitNoteServiceInterface $updateService,
        private readonly DeleteVehicleRentalLessorAgreementDebitNoteServiceInterface $deleteService,
    ) {
    }

    public function index(ListVehicleRentalLessorAgreementDebitNoteRequest $request): JsonResponse
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
            'data' => VehicleRentalLessorAgreementDebitNoteResource::collection($pageResult->items)->resolve(),
            'meta' => [
                'total' => $pageResult->total,
                'page' => $pageResult->page,
                'per_page' => $pageResult->perPage,
                'page_count' => $pageResult->pageCount(),
                'has_more' => $pageResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|VehicleRentalLessorAgreementDebitNoteResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new VehicleRentalLessorAgreementDebitNoteResource($result->valueOrFail());
    }

    public function store(UpsertVehicleRentalLessorAgreementDebitNoteRequest $request): JsonResponse|VehicleRentalLessorAgreementDebitNoteResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new VehicleRentalLessorAgreementDebitNoteResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertVehicleRentalLessorAgreementDebitNoteRequest $request, int|string $id): JsonResponse|VehicleRentalLessorAgreementDebitNoteResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'VEHICLERENTAL_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new VehicleRentalLessorAgreementDebitNoteResource($result->valueOrFail());
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
