<?php

declare(strict_types=1);

namespace Modules\Pricing\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Pricing\Application\DTOs\PricingRecordData;
use Modules\Pricing\Application\Services\PricingService;
use Modules\Pricing\Domain\Exceptions\PricingIntegrityException;
use Modules\Pricing\Domain\Exceptions\PricingRecordNotFoundException;
use Modules\Pricing\Presentation\Http\Requests\PricingRecordRequest;
use Modules\Pricing\Presentation\Http\Resources\PricingRecordResource;

class PricingResourceController extends Controller
{
    public function __construct(private readonly PricingService $pricing) {}

    public function index(Request $request, int|string $tenant, string $resource): mixed
    {
        try {
            return PricingRecordResource::collection($this->pricing->list($resource, $tenant, $this->filters($request), $this->perPage($request)));
        } catch (PricingRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function store(PricingRecordRequest $request, int|string $tenant, string $resource): JsonResponse
    {
        try {
            $record = $this->pricing->create($resource, PricingRecordData::fromArray($tenant, $request->validated()));

            return (new PricingRecordResource($record))->response()->setStatusCode(201);
        } catch (PricingIntegrityException $exception) {
            return $this->unprocessable($exception);
        } catch (PricingRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function show(int|string $tenant, string $resource, int|string $id): PricingRecordResource|JsonResponse
    {
        try {
            return new PricingRecordResource($this->pricing->find($resource, $tenant, $id));
        } catch (PricingRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function update(PricingRecordRequest $request, int|string $tenant, string $resource, int|string $id): PricingRecordResource|JsonResponse
    {
        try {
            return new PricingRecordResource($this->pricing->update($resource, $tenant, $id, PricingRecordData::fromArray($tenant, $request->validated())));
        } catch (PricingIntegrityException $exception) {
            return $this->unprocessable($exception);
        } catch (PricingRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function destroy(int|string $tenant, string $resource, int|string $id): JsonResponse
    {
        try {
            $this->pricing->delete($resource, $tenant, $id);

            return response()->json(null, 204);
        } catch (PricingIntegrityException $exception) {
            return $this->unprocessable($exception);
        } catch (PricingRecordNotFoundException $exception) {
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
            'name',
            'type',
            'currency_id',
            'is_default',
            'is_active',
            'price_list_id',
            'item_id',
            'variant_id',
            'warehouse_id',
            'warehouse_location_id',
            'batch_id',
            'serial_id',
            'uom_id',
            'discount_type',
            'supplier_id',
            'customer_id',
        ]))->filter(fn (mixed $value): bool => $value !== null && $value !== '')->all();
    }

    private function perPage(Request $request): ?int
    {
        return $request->filled('per_page') ? max(1, min((int) $request->integer('per_page'), 100)) : null;
    }

    private function notFound(PricingRecordNotFoundException $exception): JsonResponse
    {
        return response()->json(['message' => $exception->getMessage()], 404);
    }

    private function unprocessable(PricingIntegrityException $exception): JsonResponse
    {
        return response()->json(['message' => $exception->getMessage()], 422);
    }
}
