<?php

declare(strict_types=1);

namespace Modules\Payment\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Payment\Application\Contracts\UseCases\AdvancePayments\CreateAdvancePaymentServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\AdvancePayments\DeleteAdvancePaymentServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\AdvancePayments\GetAdvancePaymentServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\AdvancePayments\ListAdvancePaymentsServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\AdvancePayments\UpdateAdvancePaymentServiceInterface;
use Modules\Payment\Presentation\Http\Requests\ListAdvancePaymentRequest;
use Modules\Payment\Presentation\Http\Requests\UpsertAdvancePaymentRequest;
use Modules\Payment\Presentation\Http\Resources\AdvancePaymentResource;

final class AdvancePaymentController extends Controller
{
    public function __construct(
        private readonly ListAdvancePaymentsServiceInterface $listService,
        private readonly GetAdvancePaymentServiceInterface $getService,
        private readonly CreateAdvancePaymentServiceInterface $createService,
        private readonly UpdateAdvancePaymentServiceInterface $updateService,
        private readonly DeleteAdvancePaymentServiceInterface $deleteService,
    ) {
    }

    public function index(ListAdvancePaymentRequest $request): JsonResponse
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
            'data' => AdvancePaymentResource::collection($pageResult->items)->resolve(),
            'meta' => [
                'total' => $pageResult->total,
                'page' => $pageResult->page,
                'per_page' => $pageResult->perPage,
                'page_count' => $pageResult->pageCount(),
                'has_more' => $pageResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|AdvancePaymentResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new AdvancePaymentResource($result->valueOrFail());
    }

    public function store(UpsertAdvancePaymentRequest $request): JsonResponse|AdvancePaymentResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new AdvancePaymentResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertAdvancePaymentRequest $request, int|string $id): JsonResponse|AdvancePaymentResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'PAYMENT_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new AdvancePaymentResource($result->valueOrFail());
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