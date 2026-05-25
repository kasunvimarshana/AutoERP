<?php

declare(strict_types=1);

namespace Modules\Finance\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Finance\Application\Contracts\UseCases\PaymentTerms\CreatePaymentTermServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\PaymentTerms\DeletePaymentTermServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\PaymentTerms\GetPaymentTermServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\PaymentTerms\ListPaymentTermsServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\PaymentTerms\UpdatePaymentTermServiceInterface;
use Modules\Finance\Presentation\Http\Requests\ListPaymentTermRequest;
use Modules\Finance\Presentation\Http\Requests\UpsertPaymentTermRequest;
use Modules\Finance\Presentation\Http\Resources\PaymentTermResource;

final class PaymentTermController extends Controller
{
    public function __construct(
        private readonly ListPaymentTermsServiceInterface $listService,
        private readonly GetPaymentTermServiceInterface $getService,
        private readonly CreatePaymentTermServiceInterface $createService,
        private readonly UpdatePaymentTermServiceInterface $updateService,
        private readonly DeletePaymentTermServiceInterface $deleteService,
    ) {
    }

    public function index(ListPaymentTermRequest $request): JsonResponse
    {
        $criteria = [];
        $validated = $request->validated();

        if (isset($validated['tenant_id'])) {
            $criteria['tenant_id'] = (int) $validated['tenant_id'];
        }

        if (isset($validated['organization_unit_id'])) {
            $criteria['organization_unit_id'] = (int) $validated['organization_unit_id'];
        }

        if (isset($validated['search'])) {
            $search = trim((string) $validated['search']);
            if ($search !== '') {
                $criteria['search'] = $search;
            }
        }

        if (array_key_exists('payment_type', $validated) && $validated['payment_type'] !== null) {
            $criteria['payment_type'] = $validated['payment_type'];
        }

        if (array_key_exists('is_default', $validated) && $validated['is_default'] !== null) {
            $criteria['is_default'] = $validated['is_default'];
        }

        if (array_key_exists('is_active', $validated) && $validated['is_active'] !== null) {
            $criteria['is_active'] = $validated['is_active'];
        }

        $result = $this->listService->execute(
            $criteria,
            (int) ($validated['per_page'] ?? 0),
            (int) ($validated['page'] ?? 0),
        );

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        $page = $result->valueOrFail();
        if (! $page instanceof PagedResult) {
            return response()->json(['message' => 'Unexpected list response.'], 500);
        }

        return response()->json([
            'data' => PaymentTermResource::collection($page->items)->resolve(),
            'meta' => [
                'total' => $page->total,
                'page' => $page->page,
                'per_page' => $page->perPage,
                'page_count' => $page->pageCount(),
                'has_more' => $page->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|PaymentTermResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new PaymentTermResource($result->valueOrFail());
    }

    public function store(UpsertPaymentTermRequest $request): JsonResponse|PaymentTermResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new PaymentTermResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertPaymentTermRequest $request, int|string $id): JsonResponse|PaymentTermResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'FINANCE_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new PaymentTermResource($result->valueOrFail());
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
