<?php

declare(strict_types=1);

namespace Modules\Invoice\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Invoice\Application\DTOs\InvoiceRecordData;
use Modules\Invoice\Application\Services\InvoiceService;
use Modules\Invoice\Domain\Exceptions\InvoiceIntegrityException;
use Modules\Invoice\Domain\Exceptions\InvoiceRecordNotFoundException;
use Modules\Invoice\Presentation\Http\Requests\InvoiceRecordRequest;
use Modules\Invoice\Presentation\Http\Resources\InvoiceRecordResource;

class InvoiceResourceController extends Controller
{
    public function __construct(private readonly InvoiceService $invoices) {}

    public function index(Request $request, int|string $tenant, string $resource): mixed
    {
        try {
            return InvoiceRecordResource::collection($this->invoices->list($resource, $tenant, $this->filters($request), $this->perPage($request)));
        } catch (InvoiceRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function store(InvoiceRecordRequest $request, int|string $tenant, string $resource): JsonResponse
    {
        try {
            $record = $this->invoices->create($resource, InvoiceRecordData::fromArray($tenant, $request->validated()));

            return (new InvoiceRecordResource($record))->response()->setStatusCode(201);
        } catch (InvoiceIntegrityException $exception) {
            return $this->unprocessable($exception);
        } catch (InvoiceRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function show(int|string $tenant, string $resource, int|string $id): InvoiceRecordResource|JsonResponse
    {
        try {
            return new InvoiceRecordResource($this->invoices->find($resource, $tenant, $id));
        } catch (InvoiceRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function update(InvoiceRecordRequest $request, int|string $tenant, string $resource, int|string $id): InvoiceRecordResource|JsonResponse
    {
        try {
            return new InvoiceRecordResource($this->invoices->update($resource, $tenant, $id, InvoiceRecordData::fromArray($tenant, $request->validated())));
        } catch (InvoiceIntegrityException $exception) {
            return $this->unprocessable($exception);
        } catch (InvoiceRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function destroy(int|string $tenant, string $resource, int|string $id): JsonResponse
    {
        try {
            $this->invoices->delete($resource, $tenant, $id);

            return response()->json(null, 204);
        } catch (InvoiceIntegrityException $exception) {
            return $this->unprocessable($exception);
        } catch (InvoiceRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function filters(Request $request): array
    {
        return collect($request->only([
            'organization_unit_id',
            'invoice_id',
            'invoice_reference_id',
            'status',
            'direction',
            'invoice_type',
            'party_type',
            'party_id',
            'document_type',
            'document_id',
            'item_type',
            'item_id',
        ]))->filter(fn (mixed $value): bool => $value !== null && $value !== '')->all();
    }

    private function perPage(Request $request): ?int
    {
        return $request->filled('per_page') ? max(1, min((int) $request->integer('per_page'), 100)) : null;
    }

    private function notFound(InvoiceRecordNotFoundException $exception): JsonResponse
    {
        return response()->json(['message' => $exception->getMessage()], 404);
    }

    private function unprocessable(InvoiceIntegrityException $exception): JsonResponse
    {
        return response()->json(['message' => $exception->getMessage()], 422);
    }
}
