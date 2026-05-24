<?php

declare(strict_types=1);

namespace Modules\Sales\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Sales\Application\DTOs\SalesRecordData;
use Modules\Sales\Application\Services\SalesService;
use Modules\Sales\Domain\Exceptions\SalesIntegrityException;
use Modules\Sales\Domain\Exceptions\SalesRecordNotFoundException;
use Modules\Sales\Presentation\Http\Requests\SalesRecordRequest;
use Modules\Sales\Presentation\Http\Resources\SalesRecordResource;

class SalesResourceController extends Controller
{
    public function __construct(private readonly SalesService $sales) {}

    public function index(Request $request, int|string $tenant, string $resource): mixed
    {
        try {
            return SalesRecordResource::collection(
                $this->sales->list($resource, $tenant, $this->filters($request), $this->perPage($request)),
            );
        } catch (SalesRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function store(SalesRecordRequest $request, int|string $tenant, string $resource): JsonResponse
    {
        try {
            $record = $this->sales->create($resource, SalesRecordData::fromArray($tenant, $request->validated()));

            return (new SalesRecordResource($record))->response()->setStatusCode(201);
        } catch (SalesIntegrityException $exception) {
            return $this->unprocessable($exception);
        } catch (SalesRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function show(int|string $tenant, string $resource, int|string $id): SalesRecordResource|JsonResponse
    {
        try {
            return new SalesRecordResource($this->sales->find($resource, $tenant, $id));
        } catch (SalesRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function update(SalesRecordRequest $request, int|string $tenant, string $resource, int|string $id): SalesRecordResource|JsonResponse
    {
        try {
            return new SalesRecordResource(
                $this->sales->update($resource, $tenant, $id, SalesRecordData::fromArray($tenant, $request->validated())),
            );
        } catch (SalesIntegrityException $exception) {
            return $this->unprocessable($exception);
        } catch (SalesRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function destroy(int|string $tenant, string $resource, int|string $id): JsonResponse
    {
        try {
            $this->sales->delete($resource, $tenant, $id);

            return response()->json(null, 204);
        } catch (SalesIntegrityException $exception) {
            return $this->unprocessable($exception);
        } catch (SalesRecordNotFoundException $exception) {
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
            'customer_id',
            'warehouse_id',
            'sales_order_id',
            'sales_order_line_id',
            'gdn_header_id',
            'sales_return_id',
            'original_sales_order_id',
            'original_gdn_id',
            'original_invoice_id',
            'original_gdn_line_id',
            'original_sales_order_line_id',
            'item_id',
            'variant_id',
            'batch_id',
            'serial_id',
            'location_id',
            'status',
            'invoice_status',
            'so_number',
            'gdn_number',
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

    private function notFound(SalesRecordNotFoundException $exception): JsonResponse
    {
        return response()->json(['message' => $exception->getMessage()], 404);
    }

    private function unprocessable(SalesIntegrityException $exception): JsonResponse
    {
        return response()->json(['message' => $exception->getMessage()], 422);
    }
}
