<?php

declare(strict_types=1);

namespace Modules\HR\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\HR\Application\Contracts\UseCases\Holidays\CreateHolidayServiceInterface;
use Modules\HR\Application\Contracts\UseCases\Holidays\DeleteHolidayServiceInterface;
use Modules\HR\Application\Contracts\UseCases\Holidays\GetHolidayServiceInterface;
use Modules\HR\Application\Contracts\UseCases\Holidays\ListHolidaysServiceInterface;
use Modules\HR\Application\Contracts\UseCases\Holidays\UpdateHolidayServiceInterface;
use Modules\HR\Presentation\Http\Requests\ListHolidayRequest;
use Modules\HR\Presentation\Http\Requests\UpsertHolidayRequest;
use Modules\HR\Presentation\Http\Resources\HolidayResource;

final class HolidayController extends Controller
{
    public function __construct(
        private readonly ListHolidaysServiceInterface $listService,
        private readonly GetHolidayServiceInterface $getService,
        private readonly CreateHolidayServiceInterface $createService,
        private readonly UpdateHolidayServiceInterface $updateService,
        private readonly DeleteHolidayServiceInterface $deleteService,
    ) {
    }

    public function index(ListHolidayRequest $request): JsonResponse
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
            'data' => HolidayResource::collection($pagedResult->items)->resolve(),
            'meta' => [
                'total' => $pagedResult->total,
                'page' => $pagedResult->page,
                'per_page' => $pagedResult->perPage,
                'page_count' => $pagedResult->pageCount(),
                'has_more' => $pagedResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|HolidayResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new HolidayResource($result->valueOrFail());
    }

    public function store(UpsertHolidayRequest $request): JsonResponse|HolidayResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new HolidayResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertHolidayRequest $request, int|string $id): JsonResponse|HolidayResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'HR_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new HolidayResource($result->valueOrFail());
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