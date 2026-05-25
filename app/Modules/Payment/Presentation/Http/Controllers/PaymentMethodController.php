<?php

declare(strict_types=1);

namespace Modules\Payment\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Payment\Application\Contracts\UseCases\PaymentMethods\CreatePaymentMethodServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\PaymentMethods\DeletePaymentMethodServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\PaymentMethods\GetPaymentMethodServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\PaymentMethods\ListPaymentMethodsServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\PaymentMethods\UpdatePaymentMethodServiceInterface;
use Modules\Payment\Presentation\Http\Requests\ListPaymentMethodRequest;
use Modules\Payment\Presentation\Http\Requests\UpsertPaymentMethodRequest;
use Modules\Payment\Presentation\Http\Resources\PaymentMethodResource;

final class PaymentMethodController extends Controller
{
    public function __construct(
        private readonly ListPaymentMethodsServiceInterface $listService,
        private readonly GetPaymentMethodServiceInterface $getService,
        private readonly CreatePaymentMethodServiceInterface $createService,
        private readonly UpdatePaymentMethodServiceInterface $updateService,
        private readonly DeletePaymentMethodServiceInterface $deleteService,
    ) {
    }

    public function index(ListPaymentMethodRequest $request): JsonResponse
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
            'data' => PaymentMethodResource::collection($pageResult->items)->resolve(),
            'meta' => [
                'total' => $pageResult->total,
                'page' => $pageResult->page,
                'per_page' => $pageResult->perPage,
                'page_count' => $pageResult->pageCount(),
                'has_more' => $pageResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|PaymentMethodResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new PaymentMethodResource($result->valueOrFail());
    }

    public function store(UpsertPaymentMethodRequest $request): JsonResponse|PaymentMethodResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new PaymentMethodResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertPaymentMethodRequest $request, int|string $id): JsonResponse|PaymentMethodResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'PAYMENT_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new PaymentMethodResource($result->valueOrFail());
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