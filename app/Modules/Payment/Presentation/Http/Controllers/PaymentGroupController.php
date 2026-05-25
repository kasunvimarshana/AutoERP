<?php

declare(strict_types=1);

namespace Modules\Payment\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Payment\Application\Contracts\UseCases\PaymentGroups\CreatePaymentGroupServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\PaymentGroups\DeletePaymentGroupServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\PaymentGroups\GetPaymentGroupServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\PaymentGroups\ListPaymentGroupsServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\PaymentGroups\UpdatePaymentGroupServiceInterface;
use Modules\Payment\Presentation\Http\Requests\ListPaymentGroupRequest;
use Modules\Payment\Presentation\Http\Requests\UpsertPaymentGroupRequest;
use Modules\Payment\Presentation\Http\Resources\PaymentGroupResource;

final class PaymentGroupController extends Controller
{
    public function __construct(
        private readonly ListPaymentGroupsServiceInterface $listService,
        private readonly GetPaymentGroupServiceInterface $getService,
        private readonly CreatePaymentGroupServiceInterface $createService,
        private readonly UpdatePaymentGroupServiceInterface $updateService,
        private readonly DeletePaymentGroupServiceInterface $deleteService,
    ) {
    }

    public function index(ListPaymentGroupRequest $request): JsonResponse
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
            'data' => PaymentGroupResource::collection($pageResult->items)->resolve(),
            'meta' => [
                'total' => $pageResult->total,
                'page' => $pageResult->page,
                'per_page' => $pageResult->perPage,
                'page_count' => $pageResult->pageCount(),
                'has_more' => $pageResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|PaymentGroupResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new PaymentGroupResource($result->valueOrFail());
    }

    public function store(UpsertPaymentGroupRequest $request): JsonResponse|PaymentGroupResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new PaymentGroupResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertPaymentGroupRequest $request, int|string $id): JsonResponse|PaymentGroupResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'PAYMENT_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new PaymentGroupResource($result->valueOrFail());
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