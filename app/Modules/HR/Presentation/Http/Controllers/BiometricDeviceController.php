<?php

declare(strict_types=1);

namespace Modules\HR\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\HR\Application\Contracts\UseCases\BiometricDevices\CreateBiometricDeviceServiceInterface;
use Modules\HR\Application\Contracts\UseCases\BiometricDevices\DeleteBiometricDeviceServiceInterface;
use Modules\HR\Application\Contracts\UseCases\BiometricDevices\GetBiometricDeviceServiceInterface;
use Modules\HR\Application\Contracts\UseCases\BiometricDevices\ListBiometricDevicesServiceInterface;
use Modules\HR\Application\Contracts\UseCases\BiometricDevices\UpdateBiometricDeviceServiceInterface;
use Modules\HR\Presentation\Http\Requests\ListBiometricDeviceRequest;
use Modules\HR\Presentation\Http\Requests\UpsertBiometricDeviceRequest;
use Modules\HR\Presentation\Http\Resources\BiometricDeviceResource;

final class BiometricDeviceController extends Controller
{
    public function __construct(
        private readonly ListBiometricDevicesServiceInterface $listService,
        private readonly GetBiometricDeviceServiceInterface $getService,
        private readonly CreateBiometricDeviceServiceInterface $createService,
        private readonly UpdateBiometricDeviceServiceInterface $updateService,
        private readonly DeleteBiometricDeviceServiceInterface $deleteService,
    ) {
    }

    public function index(ListBiometricDeviceRequest $request): JsonResponse
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
            'data' => BiometricDeviceResource::collection($pagedResult->items)->resolve(),
            'meta' => [
                'total' => $pagedResult->total,
                'page' => $pagedResult->page,
                'per_page' => $pagedResult->perPage,
                'page_count' => $pagedResult->pageCount(),
                'has_more' => $pagedResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|BiometricDeviceResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new BiometricDeviceResource($result->valueOrFail());
    }

    public function store(UpsertBiometricDeviceRequest $request): JsonResponse|BiometricDeviceResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new BiometricDeviceResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertBiometricDeviceRequest $request, int|string $id): JsonResponse|BiometricDeviceResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'HR_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new BiometricDeviceResource($result->valueOrFail());
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
