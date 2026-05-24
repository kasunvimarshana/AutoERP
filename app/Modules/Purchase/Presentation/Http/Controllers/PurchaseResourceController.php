<?php

declare(strict_types=1);

namespace Modules\Purchase\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Purchase\Application\DTOs\PurchaseRecordData;
use Modules\Purchase\Application\Services\PurchaseService;
use Modules\Purchase\Domain\Exceptions\PurchaseIntegrityException;
use Modules\Purchase\Domain\Exceptions\PurchaseRecordNotFoundException;
use Modules\Purchase\Presentation\Http\Requests\PurchaseRecordRequest;
use Modules\Purchase\Presentation\Http\Resources\PurchaseRecordResource;

class PurchaseResourceController extends Controller
{
    public function __construct(private readonly PurchaseService $purchases) {}

    public function index(Request $request, int|string $tenant, string $resource): mixed
    {
        try {
            return PurchaseRecordResource::collection(
                $this->purchases->list($resource, $tenant, $this->filters($request), $this->perPage($request)),
            );
        } catch (PurchaseRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function store(PurchaseRecordRequest $request, int|string $tenant, string $resource): JsonResponse
    {
        try {
            $record = $this->purchases->create($resource, PurchaseRecordData::fromArray($tenant, $request->validated()));

            return (new PurchaseRecordResource($record))->response()->setStatusCode(201);
        } catch (PurchaseIntegrityException $exception) {
            return $this->unprocessable($exception);
        } catch (PurchaseRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function show(int|string $tenant, string $resource, int|string $id): PurchaseRecordResource|JsonResponse
    {
        try {
            return new PurchaseRecordResource($this->purchases->find($resource, $tenant, $id));
        } catch (PurchaseRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function update(PurchaseRecordRequest $request, int|string $tenant, string $resource, int|string $id): PurchaseRecordResource|JsonResponse
    {
        try {
            return new PurchaseRecordResource(
                $this->purchases->update($resource, $tenant, $id, PurchaseRecordData::fromArray($tenant, $request->validated())),
            );
        } catch (PurchaseIntegrityException $exception) {
            return $this->unprocessable($exception);
        } catch (PurchaseRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function destroy(int|string $tenant, string $resource, int|string $id): JsonResponse
    {
        try {
            $this->purchases->delete($resource, $tenant, $id);

            return response()->json(null, 204);
        } catch (PurchaseIntegrityException $exception) {
            return $this->unprocessable($exception);
        } catch (PurchaseRecordNotFoundException $exception) {
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
            'reference',
            'supplier_id',
            'warehouse_id',
            'purchase_order_id',
            'purchase_order_line_id',
            'grn_header_id',
            'purchase_return_id',
            'original_purchase_order_id',
            'original_grn_id',
            'original_invoice_id',
            'original_grn_line_id',
            'original_purchase_order_line_id',
            'item_id',
            'variant_id',
            'batch_id',
            'serial_id',
            'location_id',
            'status',
            'invoice_status',
            'po_number',
            'grn_number',
            'return_number',
            'return_reason',
            'condition',
            'disposition',
        ]))->filter(fn (mixed $value): bool => $value !== null && $value !== '')->all();
    }

    private function perPage(Request $request): ?int
    {
        return $request->filled('per_page') ? max(1, min((int) $request->integer('per_page'), 100)) : null;
    }

    private function notFound(PurchaseRecordNotFoundException $exception): JsonResponse
    {
        return response()->json(['message' => $exception->getMessage()], 404);
    }

    private function unprocessable(PurchaseIntegrityException $exception): JsonResponse
    {
        return response()->json(['message' => $exception->getMessage()], 422);
    }
}
