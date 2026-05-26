<?php

declare(strict_types=1);

namespace Modules\VehicleService\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceJobCards\CreateVehicleServiceJobCardServiceInterface;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceJobCards\DeleteVehicleServiceJobCardServiceInterface;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceJobCards\GetVehicleServiceJobCardServiceInterface;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceJobCards\ListVehicleServiceJobCardsServiceInterface;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceJobCards\UpdateVehicleServiceJobCardServiceInterface;
use Modules\VehicleService\Presentation\Http\Requests\ListVehicleServiceJobCardRequest;
use Modules\VehicleService\Presentation\Http\Requests\UpsertVehicleServiceJobCardRequest;
use Modules\VehicleService\Presentation\Http\Resources\VehicleServiceJobCardResource;
use Modules\VehicleService\Application\DTOs\CompleteJobCardDTO;
use Modules\VehicleService\Application\DTOs\CreateJobCardDTO;
use Modules\VehicleService\Application\DTOs\CreateServiceInvoiceDTO;
use Modules\VehicleService\Application\Orchestrators\VehicleServiceOrchestrator;
use Throwable;

final class VehicleServiceJobCardController extends Controller
{
    public function __construct(
        private readonly ListVehicleServiceJobCardsServiceInterface $listService,
        private readonly GetVehicleServiceJobCardServiceInterface $getService,
        private readonly CreateVehicleServiceJobCardServiceInterface $createService,
        private readonly UpdateVehicleServiceJobCardServiceInterface $updateService,
        private readonly DeleteVehicleServiceJobCardServiceInterface $deleteService,
        private readonly VehicleServiceOrchestrator $orchestrator,
    ) {
    }

    public function index(ListVehicleServiceJobCardRequest $request): JsonResponse
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
            'data' => VehicleServiceJobCardResource::collection($pageResult->items)->resolve(),
            'meta' => [
                'total' => $pageResult->total,
                'page' => $pageResult->page,
                'per_page' => $pageResult->perPage,
                'page_count' => $pageResult->pageCount(),
                'has_more' => $pageResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|VehicleServiceJobCardResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new VehicleServiceJobCardResource($result->valueOrFail());
    }

    public function store(UpsertVehicleServiceJobCardRequest $request): JsonResponse|VehicleServiceJobCardResource
    {
        try {
            return (new VehicleServiceJobCardResource(
                $this->orchestrator->create(new CreateJobCardDTO($request->validated())),
            ))->response()->setStatusCode(201);
        } catch (Throwable $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }

    public function update(UpsertVehicleServiceJobCardRequest $request, int|string $id): JsonResponse|VehicleServiceJobCardResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'VEHICLESERVICE_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new VehicleServiceJobCardResource($result->valueOrFail());
    }

    public function destroy(int|string $id): JsonResponse
    {
        $result = $this->deleteService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return response()->json(null, 204);
    }

    public function start(int|string $id): JsonResponse|VehicleServiceJobCardResource
    {
        try {
            return new VehicleServiceJobCardResource($this->orchestrator->start((int) $id));
        } catch (Throwable $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }

    public function complete(int|string $id): JsonResponse|VehicleServiceJobCardResource
    {
        try {
            return new VehicleServiceJobCardResource($this->orchestrator->complete(new CompleteJobCardDTO((int) $id)));
        } catch (Throwable $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }

    public function invoice(int|string $id): JsonResponse|VehicleServiceJobCardResource
    {
        try {
            return new VehicleServiceJobCardResource($this->orchestrator->invoice(new CreateServiceInvoiceDTO((int) $id)));
        } catch (Throwable $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }
}
