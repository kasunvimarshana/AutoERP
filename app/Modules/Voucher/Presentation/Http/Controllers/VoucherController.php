<?php

declare(strict_types=1);

namespace Modules\Voucher\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Voucher\Application\Contracts\UseCases\Vouchers\CreateVoucherServiceInterface;
use Modules\Voucher\Application\Contracts\UseCases\Vouchers\DeleteVoucherServiceInterface;
use Modules\Voucher\Application\Contracts\UseCases\Vouchers\GetVoucherServiceInterface;
use Modules\Voucher\Application\Contracts\UseCases\Vouchers\ListVouchersServiceInterface;
use Modules\Voucher\Application\Contracts\UseCases\Vouchers\UpdateVoucherServiceInterface;
use Modules\Voucher\Presentation\Http\Requests\ListVoucherRequest;
use Modules\Voucher\Presentation\Http\Requests\UpsertVoucherRequest;
use Modules\Voucher\Presentation\Http\Resources\VoucherResource;

final class VoucherController extends Controller
{
    public function __construct(
        private readonly ListVouchersServiceInterface $listService,
        private readonly GetVoucherServiceInterface $getService,
        private readonly CreateVoucherServiceInterface $createService,
        private readonly UpdateVoucherServiceInterface $updateService,
        private readonly DeleteVoucherServiceInterface $deleteService,
    ) {
    }

    public function index(ListVoucherRequest $request): JsonResponse
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
            'data' => VoucherResource::collection($pageResult->items)->resolve(),
            'meta' => [
                'total' => $pageResult->total,
                'page' => $pageResult->page,
                'per_page' => $pageResult->perPage,
                'page_count' => $pageResult->pageCount(),
                'has_more' => $pageResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|VoucherResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new VoucherResource($result->valueOrFail());
    }

    public function store(UpsertVoucherRequest $request): JsonResponse|VoucherResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new VoucherResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertVoucherRequest $request, int|string $id): JsonResponse|VoucherResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'VOUCHER_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new VoucherResource($result->valueOrFail());
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