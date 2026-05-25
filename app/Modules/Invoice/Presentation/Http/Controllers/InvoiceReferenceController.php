<?php

declare(strict_types=1);

namespace Modules\Invoice\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Invoice\Application\Contracts\UseCases\InvoiceReferences\CreateInvoiceReferenceServiceInterface;
use Modules\Invoice\Application\Contracts\UseCases\InvoiceReferences\DeleteInvoiceReferenceServiceInterface;
use Modules\Invoice\Application\Contracts\UseCases\InvoiceReferences\GetInvoiceReferenceServiceInterface;
use Modules\Invoice\Application\Contracts\UseCases\InvoiceReferences\ListInvoiceReferencesServiceInterface;
use Modules\Invoice\Application\Contracts\UseCases\InvoiceReferences\UpdateInvoiceReferenceServiceInterface;
use Modules\Invoice\Presentation\Http\Requests\ListInvoiceReferenceRequest;
use Modules\Invoice\Presentation\Http\Requests\UpsertInvoiceReferenceRequest;
use Modules\Invoice\Presentation\Http\Resources\InvoiceReferenceResource;

final class InvoiceReferenceController extends Controller
{
    public function __construct(
        private readonly ListInvoiceReferencesServiceInterface $listService,
        private readonly GetInvoiceReferenceServiceInterface $getService,
        private readonly CreateInvoiceReferenceServiceInterface $createService,
        private readonly UpdateInvoiceReferenceServiceInterface $updateService,
        private readonly DeleteInvoiceReferenceServiceInterface $deleteService,
    ) {
    }

    public function index(ListInvoiceReferenceRequest $request): JsonResponse
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
            'data' => InvoiceReferenceResource::collection($pageResult->items)->resolve(),
            'meta' => [
                'total' => $pageResult->total,
                'page' => $pageResult->page,
                'per_page' => $pageResult->perPage,
                'page_count' => $pageResult->pageCount(),
                'has_more' => $pageResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|InvoiceReferenceResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new InvoiceReferenceResource($result->valueOrFail());
    }

    public function store(UpsertInvoiceReferenceRequest $request): JsonResponse|InvoiceReferenceResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new InvoiceReferenceResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertInvoiceReferenceRequest $request, int|string $id): JsonResponse|InvoiceReferenceResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'INVOICE_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new InvoiceReferenceResource($result->valueOrFail());
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