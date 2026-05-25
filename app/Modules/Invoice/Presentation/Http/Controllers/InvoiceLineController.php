<?php

declare(strict_types=1);

namespace Modules\Invoice\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Invoice\Application\Contracts\UseCases\InvoiceLines\CreateInvoiceLineServiceInterface;
use Modules\Invoice\Application\Contracts\UseCases\InvoiceLines\DeleteInvoiceLineServiceInterface;
use Modules\Invoice\Application\Contracts\UseCases\InvoiceLines\GetInvoiceLineServiceInterface;
use Modules\Invoice\Application\Contracts\UseCases\InvoiceLines\ListInvoiceLinesServiceInterface;
use Modules\Invoice\Application\Contracts\UseCases\InvoiceLines\UpdateInvoiceLineServiceInterface;
use Modules\Invoice\Presentation\Http\Requests\ListInvoiceLineRequest;
use Modules\Invoice\Presentation\Http\Requests\UpsertInvoiceLineRequest;
use Modules\Invoice\Presentation\Http\Resources\InvoiceLineResource;

final class InvoiceLineController extends Controller
{
    public function __construct(
        private readonly ListInvoiceLinesServiceInterface $listService,
        private readonly GetInvoiceLineServiceInterface $getService,
        private readonly CreateInvoiceLineServiceInterface $createService,
        private readonly UpdateInvoiceLineServiceInterface $updateService,
        private readonly DeleteInvoiceLineServiceInterface $deleteService,
    ) {
    }

    public function index(ListInvoiceLineRequest $request): JsonResponse
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
            'data' => InvoiceLineResource::collection($pageResult->items)->resolve(),
            'meta' => [
                'total' => $pageResult->total,
                'page' => $pageResult->page,
                'per_page' => $pageResult->perPage,
                'page_count' => $pageResult->pageCount(),
                'has_more' => $pageResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|InvoiceLineResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new InvoiceLineResource($result->valueOrFail());
    }

    public function store(UpsertInvoiceLineRequest $request): JsonResponse|InvoiceLineResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new InvoiceLineResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertInvoiceLineRequest $request, int|string $id): JsonResponse|InvoiceLineResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'INVOICE_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new InvoiceLineResource($result->valueOrFail());
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