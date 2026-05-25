<?php

declare(strict_types=1);

namespace Modules\HR\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\HR\Application\Contracts\UseCases\Payslips\CreatePayslipServiceInterface;
use Modules\HR\Application\Contracts\UseCases\Payslips\DeletePayslipServiceInterface;
use Modules\HR\Application\Contracts\UseCases\Payslips\GetPayslipServiceInterface;
use Modules\HR\Application\Contracts\UseCases\Payslips\ListPayslipsServiceInterface;
use Modules\HR\Application\Contracts\UseCases\Payslips\UpdatePayslipServiceInterface;
use Modules\HR\Presentation\Http\Requests\ListPayslipRequest;
use Modules\HR\Presentation\Http\Requests\UpsertPayslipRequest;
use Modules\HR\Presentation\Http\Resources\PayslipResource;

final class PayslipController extends Controller
{
    public function __construct(
        private readonly ListPayslipsServiceInterface $listService,
        private readonly GetPayslipServiceInterface $getService,
        private readonly CreatePayslipServiceInterface $createService,
        private readonly UpdatePayslipServiceInterface $updateService,
        private readonly DeletePayslipServiceInterface $deleteService,
    ) {
    }

    public function index(ListPayslipRequest $request): JsonResponse
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
            'data' => PayslipResource::collection($pagedResult->items)->resolve(),
            'meta' => [
                'total' => $pagedResult->total,
                'page' => $pagedResult->page,
                'per_page' => $pagedResult->perPage,
                'page_count' => $pagedResult->pageCount(),
                'has_more' => $pagedResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|PayslipResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new PayslipResource($result->valueOrFail());
    }

    public function store(UpsertPayslipRequest $request): JsonResponse|PayslipResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new PayslipResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertPayslipRequest $request, int|string $id): JsonResponse|PayslipResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'HR_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new PayslipResource($result->valueOrFail());
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