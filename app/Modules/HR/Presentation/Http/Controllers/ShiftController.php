<?php

declare(strict_types=1);

namespace Modules\HR\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\HR\Application\Contracts\UseCases\Shifts\CreateShiftServiceInterface;
use Modules\HR\Application\Contracts\UseCases\Shifts\DeleteShiftServiceInterface;
use Modules\HR\Application\Contracts\UseCases\Shifts\GetShiftServiceInterface;
use Modules\HR\Application\Contracts\UseCases\Shifts\ListShiftsServiceInterface;
use Modules\HR\Application\Contracts\UseCases\Shifts\UpdateShiftServiceInterface;
use Modules\HR\Presentation\Http\Requests\ListShiftRequest;
use Modules\HR\Presentation\Http\Requests\UpsertShiftRequest;
use Modules\HR\Presentation\Http\Resources\ShiftResource;

final class ShiftController extends Controller
{
    public function __construct(
        private readonly ListShiftsServiceInterface $listService,
        private readonly GetShiftServiceInterface $getService,
        private readonly CreateShiftServiceInterface $createService,
        private readonly UpdateShiftServiceInterface $updateService,
        private readonly DeleteShiftServiceInterface $deleteService,
    ) {
    }

    public function index(ListShiftRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $perPage = (int) ($validated['per_page'] ?? 0);
        $page = (int) ($validated['page'] ?? 0);
        unset($validated['per_page'], $validated['page']);

        $result = $this->listService->execute($validated, $perPage, $page);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        $pagedResult = $result->valueOrFail();
        if (! $pagedResult instanceof PagedResult) {
            return response()->json(['message' => 'Unexpected list response.'], 500);
        }

        return response()->json([
            'data' => ShiftResource::collection($pagedResult->items)->resolve(),
            'meta' => [
                'total' => $pagedResult->total,
                'page' => $pagedResult->page,
                'per_page' => $pagedResult->perPage,
                'page_count' => $pagedResult->pageCount(),
                'has_more' => $pagedResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|ShiftResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new ShiftResource($result->valueOrFail());
    }

    public function store(UpsertShiftRequest $request): JsonResponse|ShiftResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new ShiftResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertShiftRequest $request, int|string $id): JsonResponse|ShiftResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'HR_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new ShiftResource($result->valueOrFail());
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
