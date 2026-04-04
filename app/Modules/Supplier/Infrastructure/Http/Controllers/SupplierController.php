<?php
namespace Modules\Supplier\Infrastructure\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Supplier\Application\Contracts\CreateSupplierServiceInterface;
use Modules\Supplier\Application\Contracts\DeleteSupplierServiceInterface;
use Modules\Supplier\Application\Contracts\UpdateSupplierServiceInterface;
use Modules\Supplier\Application\DTOs\SupplierData;
use Modules\Supplier\Domain\RepositoryInterfaces\SupplierRepositoryInterface;
use Modules\Supplier\Infrastructure\Http\Resources\SupplierResource;

class SupplierController extends Controller
{
    public function __construct(
        private readonly SupplierRepositoryInterface $repository,
        private readonly CreateSupplierServiceInterface $createService,
        private readonly UpdateSupplierServiceInterface $updateService,
        private readonly DeleteSupplierServiceInterface $deleteService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = (int) $request->query('tenant_id');
        $filters  = $request->only(['status', 'code']);
        $suppliers = $this->repository->findAll($tenantId, $filters);
        return response()->json($suppliers);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id'  => 'required|integer',
            'name'       => 'required|string|max:255',
            'code'       => 'required|string|max:50',
            'status'     => 'sometimes|string|in:active,inactive,blacklisted',
            'email'      => 'sometimes|nullable|email',
            'phone'      => 'sometimes|nullable|string|max:50',
            'address'    => 'sometimes|nullable|string',
            'city'       => 'sometimes|nullable|string|max:100',
            'country'    => 'sometimes|nullable|string|max:100',
            'tax_number' => 'sometimes|nullable|string|max:100',
            'currency'   => 'sometimes|nullable|string|max:3',
            'notes'      => 'sometimes|nullable|string',
        ]);

        $data = new SupplierData(
            tenantId:  $validated['tenant_id'],
            name:      $validated['name'],
            code:      $validated['code'],
            status:    $validated['status'] ?? 'active',
            email:     $validated['email'] ?? null,
            phone:     $validated['phone'] ?? null,
            address:   $validated['address'] ?? null,
            city:      $validated['city'] ?? null,
            country:   $validated['country'] ?? null,
            taxNumber: $validated['tax_number'] ?? null,
            currency:  $validated['currency'] ?? 'USD',
            notes:     $validated['notes'] ?? null,
        );

        $supplier = $this->createService->execute($data);
        return response()->json(new SupplierResource($supplier), 201);
    }

    public function show(int $id): JsonResponse
    {
        $supplier = $this->repository->findById($id);
        if (!$supplier) {
            return response()->json(['message' => 'Not found'], 404);
        }
        return response()->json(new SupplierResource($supplier));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id'  => 'sometimes|integer',
            'name'       => 'sometimes|string|max:255',
            'code'       => 'sometimes|string|max:50',
            'status'     => 'sometimes|string|in:active,inactive,blacklisted',
            'email'      => 'sometimes|nullable|email',
            'phone'      => 'sometimes|nullable|string|max:50',
            'address'    => 'sometimes|nullable|string',
            'city'       => 'sometimes|nullable|string|max:100',
            'country'    => 'sometimes|nullable|string|max:100',
            'tax_number' => 'sometimes|nullable|string|max:100',
            'currency'   => 'sometimes|nullable|string|max:3',
            'notes'      => 'sometimes|nullable|string',
        ]);

        $existing = $this->repository->findById($id);
        if (!$existing) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $data = new SupplierData(
            tenantId:  $validated['tenant_id'] ?? $existing->tenantId,
            name:      $validated['name'] ?? $existing->name,
            code:      $validated['code'] ?? $existing->code,
            status:    $validated['status'] ?? $existing->status,
            email:     $validated['email'] ?? $existing->email,
            phone:     $validated['phone'] ?? $existing->phone,
            address:   $validated['address'] ?? $existing->address,
            city:      $validated['city'] ?? $existing->city,
            country:   $validated['country'] ?? $existing->country,
            taxNumber: $validated['tax_number'] ?? $existing->taxNumber,
            currency:  $validated['currency'] ?? $existing->currency,
            notes:     $validated['notes'] ?? $existing->notes,
        );

        $updated = $this->updateService->execute($id, $data);
        return response()->json(new SupplierResource($updated));
    }

    public function destroy(int $id): JsonResponse
    {
        $supplier = $this->repository->findById($id);
        if (!$supplier) {
            return response()->json(['message' => 'Not found'], 404);
        }
        $this->deleteService->execute($id);
        return response()->json(null, 204);
    }
}
