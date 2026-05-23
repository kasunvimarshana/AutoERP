<?php

declare(strict_types=1);

namespace Modules\Customer\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Customer\Application\DTOs\CustomerRecordData;
use Modules\Customer\Application\Services\CustomerService;
use Modules\Customer\Domain\Exceptions\CustomerIntegrityException;
use Modules\Customer\Domain\Exceptions\CustomerRecordNotFoundException;
use Modules\Customer\Presentation\Http\Requests\CustomerRecordRequest;
use Modules\Customer\Presentation\Http\Resources\CustomerRecordResource;

class CustomerResourceController extends Controller
{
    public function __construct(private readonly CustomerService $customers) {}

    public function index(Request $request, int|string $tenant, string $resource): mixed
    {
        try {
            return CustomerRecordResource::collection($this->customers->list($resource, $tenant, $this->filters($request), $this->perPage($request)));
        } catch (CustomerRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function store(CustomerRecordRequest $request, int|string $tenant, string $resource): JsonResponse
    {
        try {
            $record = $this->customers->create($resource, CustomerRecordData::fromArray($tenant, $request->validated()));

            return (new CustomerRecordResource($record))->response()->setStatusCode(201);
        } catch (CustomerIntegrityException $exception) {
            return $this->unprocessable($exception);
        } catch (CustomerRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function show(int|string $tenant, string $resource, int|string $id): CustomerRecordResource|JsonResponse
    {
        try {
            return new CustomerRecordResource($this->customers->find($resource, $tenant, $id));
        } catch (CustomerRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function update(CustomerRecordRequest $request, int|string $tenant, string $resource, int|string $id): CustomerRecordResource|JsonResponse
    {
        try {
            return new CustomerRecordResource($this->customers->update($resource, $tenant, $id, CustomerRecordData::fromArray($tenant, $request->validated())));
        } catch (CustomerIntegrityException $exception) {
            return $this->unprocessable($exception);
        } catch (CustomerRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function destroy(int|string $tenant, string $resource, int|string $id): JsonResponse
    {
        try {
            $this->customers->delete($resource, $tenant, $id);

            return response()->json(null, 204);
        } catch (CustomerIntegrityException $exception) {
            return $this->unprocessable($exception);
        } catch (CustomerRecordNotFoundException $exception) {
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
            'ar_account_id',
            'customer_id',
            'email',
            'country_id',
            'vehicle_id',
            'is_primary',
            'is_default',
            'is_current',
            'is_active',
        ]))->filter(fn (mixed $value): bool => $value !== null && $value !== '')->all();
    }

    private function perPage(Request $request): ?int
    {
        return $request->filled('per_page') ? max(1, min((int) $request->integer('per_page'), 100)) : null;
    }

    private function notFound(CustomerRecordNotFoundException $exception): JsonResponse
    {
        return response()->json(['message' => $exception->getMessage()], 404);
    }

    private function unprocessable(CustomerIntegrityException $exception): JsonResponse
    {
        return response()->json(['message' => $exception->getMessage()], 422);
    }
}
