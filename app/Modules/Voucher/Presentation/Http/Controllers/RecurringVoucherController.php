<?php

declare(strict_types=1);

namespace Modules\Voucher\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Voucher\Application\Contracts\UseCases\RecurringVouchers\CreateRecurringVoucherServiceInterface;
use Modules\Voucher\Application\Contracts\UseCases\RecurringVouchers\DeleteRecurringVoucherServiceInterface;
use Modules\Voucher\Application\Contracts\UseCases\RecurringVouchers\GetRecurringVoucherServiceInterface;
use Modules\Voucher\Application\Contracts\UseCases\RecurringVouchers\ListRecurringVouchersServiceInterface;
use Modules\Voucher\Application\Contracts\UseCases\RecurringVouchers\UpdateRecurringVoucherServiceInterface;
use Modules\Voucher\Presentation\Http\Requests\ListRecurringVoucherRequest;
use Modules\Voucher\Presentation\Http\Requests\UpsertRecurringVoucherRequest;
use Modules\Voucher\Presentation\Http\Resources\RecurringVoucherResource;

final class RecurringVoucherController extends Controller
{
    public function __construct(
        private readonly ListRecurringVouchersServiceInterface $listService,
        private readonly GetRecurringVoucherServiceInterface $getService,
        private readonly CreateRecurringVoucherServiceInterface $createService,
        private readonly UpdateRecurringVoucherServiceInterface $updateService,
        private readonly DeleteRecurringVoucherServiceInterface $deleteService,
    ) {
    }

    public function index(ListRecurringVoucherRequest $request): JsonResponse
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
            'data' => RecurringVoucherResource::collection($pageResult->items)->resolve(),
            'meta' => [
                'total' => $pageResult->total,
                'page' => $pageResult->page,
                'per_page' => $pageResult->perPage,
                'page_count' => $pageResult->pageCount(),
                'has_more' => $pageResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|RecurringVoucherResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new RecurringVoucherResource($result->valueOrFail());
    }

    public function store(UpsertRecurringVoucherRequest $request): JsonResponse|RecurringVoucherResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new RecurringVoucherResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertRecurringVoucherRequest $request, int|string $id): JsonResponse|RecurringVoucherResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'VOUCHER_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new RecurringVoucherResource($result->valueOrFail());
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