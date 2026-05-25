<?php

declare(strict_types=1);

namespace Modules\Invoice\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Invoice\Application\Contracts\UseCases\Invoices\CreateInvoiceServiceInterface;
use Modules\Invoice\Application\Contracts\UseCases\Invoices\DeleteInvoiceServiceInterface;
use Modules\Invoice\Application\Contracts\UseCases\Invoices\GetInvoiceServiceInterface;
use Modules\Invoice\Application\Contracts\UseCases\Invoices\ListInvoicesServiceInterface;
use Modules\Invoice\Application\Contracts\UseCases\Invoices\UpdateInvoiceServiceInterface;
use Modules\Invoice\Presentation\Http\Requests\ListInvoiceRequest;
use Modules\Invoice\Presentation\Http\Requests\UpsertInvoiceRequest;
use Modules\Invoice\Presentation\Http\Resources\InvoiceResource;

final class InvoiceController extends Controller
{
    public function __construct(
        private readonly ListInvoicesServiceInterface $listService,
        private readonly GetInvoiceServiceInterface $getService,
        private readonly CreateInvoiceServiceInterface $createService,
        private readonly UpdateInvoiceServiceInterface $updateService,
        private readonly DeleteInvoiceServiceInterface $deleteService,
    ) {
    }

    public function index(ListInvoiceRequest $request): JsonResponse
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
            'data' => InvoiceResource::collection($pageResult->items)->resolve(),
            'meta' => [
                'total' => $pageResult->total,
                'page' => $pageResult->page,
                'per_page' => $pageResult->perPage,
                'page_count' => $pageResult->pageCount(),
                'has_more' => $pageResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|InvoiceResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new InvoiceResource($result->valueOrFail());
    }

    public function store(UpsertInvoiceRequest $request): JsonResponse|InvoiceResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new InvoiceResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertInvoiceRequest $request, int|string $id): JsonResponse|InvoiceResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'INVOICE_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new InvoiceResource($result->valueOrFail());
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