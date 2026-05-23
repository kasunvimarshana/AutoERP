<?php

declare(strict_types=1);

namespace Modules\Supplier\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Supplier\Application\DTOs\SupplierRecordData;
use Modules\Supplier\Application\Services\SupplierService;
use Modules\Supplier\Domain\Exceptions\SupplierIntegrityException;
use Modules\Supplier\Domain\Exceptions\SupplierRecordNotFoundException;
use Modules\Supplier\Presentation\Http\Requests\SupplierRecordRequest;
use Modules\Supplier\Presentation\Http\Resources\SupplierRecordResource;

class SupplierResourceController extends Controller
{
    public function __construct(private readonly SupplierService $suppliers) {}

    public function index(Request $request, int|string $tenant, string $resource): mixed
    {
        try {
            return SupplierRecordResource::collection($this->suppliers->list($resource, $tenant, $this->filters($request), $this->perPage($request)));
        } catch (SupplierRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function store(SupplierRecordRequest $request, int|string $tenant, string $resource): JsonResponse
    {
        try {
            $record = $this->suppliers->create($resource, SupplierRecordData::fromArray($tenant, $request->validated()));

            return (new SupplierRecordResource($record))->response()->setStatusCode(201);
        } catch (SupplierIntegrityException $exception) {
            return $this->unprocessable($exception);
        } catch (SupplierRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function show(int|string $tenant, string $resource, int|string $id): SupplierRecordResource|JsonResponse
    {
        try {
            return new SupplierRecordResource($this->suppliers->find($resource, $tenant, $id));
        } catch (SupplierRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function update(SupplierRecordRequest $request, int|string $tenant, string $resource, int|string $id): SupplierRecordResource|JsonResponse
    {
        try {
            return new SupplierRecordResource($this->suppliers->update($resource, $tenant, $id, SupplierRecordData::fromArray($tenant, $request->validated())));
        } catch (SupplierIntegrityException $exception) {
            return $this->unprocessable($exception);
        } catch (SupplierRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function destroy(int|string $tenant, string $resource, int|string $id): JsonResponse
    {
        try {
            $this->suppliers->delete($resource, $tenant, $id);

            return response()->json(null, 204);
        } catch (SupplierIntegrityException $exception) {
            return $this->unprocessable($exception);
        } catch (SupplierRecordNotFoundException $exception) {
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
            'user_id',
            'code',
            'registration_number',
            'type',
            'status',
            'currency_id',
            'ap_account_id',
            'supplier_id',
            'email',
            'country_id',
            'vehicle_id',
            'item_id',
            'variant_id',
            'is_primary',
            'is_default',
            'is_current',
            'is_active',
            'is_preferred',
        ]))->filter(fn (mixed $value): bool => $value !== null && $value !== '')->all();
    }

    private function perPage(Request $request): ?int
    {
        return $request->filled('per_page') ? max(1, min((int) $request->integer('per_page'), 100)) : null;
    }

    private function notFound(SupplierRecordNotFoundException $exception): JsonResponse
    {
        return response()->json(['message' => $exception->getMessage()], 404);
    }

    private function unprocessable(SupplierIntegrityException $exception): JsonResponse
    {
        return response()->json(['message' => $exception->getMessage()], 422);
    }
}
