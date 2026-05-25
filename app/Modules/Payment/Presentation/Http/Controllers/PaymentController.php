<?php

declare(strict_types=1);

namespace Modules\Payment\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Payment\Application\Contracts\UseCases\Payments\CreatePaymentServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\Payments\DeletePaymentServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\Payments\GetPaymentServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\Payments\ListPaymentsServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\Payments\UpdatePaymentServiceInterface;
use Modules\Payment\Presentation\Http\Requests\ListPaymentRequest;
use Modules\Payment\Presentation\Http\Requests\UpsertPaymentRequest;
use Modules\Payment\Presentation\Http\Resources\PaymentResource;

final class PaymentController extends Controller
{
    public function __construct(
        private readonly ListPaymentsServiceInterface $listService,
        private readonly GetPaymentServiceInterface $getService,
        private readonly CreatePaymentServiceInterface $createService,
        private readonly UpdatePaymentServiceInterface $updateService,
        private readonly DeletePaymentServiceInterface $deleteService,
    ) {
    }

    public function index(ListPaymentRequest $request): JsonResponse
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
            'data' => PaymentResource::collection($pageResult->items)->resolve(),
            'meta' => [
                'total' => $pageResult->total,
                'page' => $pageResult->page,
                'per_page' => $pageResult->perPage,
                'page_count' => $pageResult->pageCount(),
                'has_more' => $pageResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|PaymentResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new PaymentResource($result->valueOrFail());
    }

    public function store(UpsertPaymentRequest $request): JsonResponse|PaymentResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new PaymentResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertPaymentRequest $request, int|string $id): JsonResponse|PaymentResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'PAYMENT_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new PaymentResource($result->valueOrFail());
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