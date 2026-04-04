<?php
namespace Modules\Supplier\Infrastructure\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Supplier\Application\Contracts\CreateSupplierContactServiceInterface;
use Modules\Supplier\Application\Contracts\DeleteSupplierContactServiceInterface;
use Modules\Supplier\Application\Contracts\UpdateSupplierContactServiceInterface;
use Modules\Supplier\Application\DTOs\SupplierContactData;
use Modules\Supplier\Domain\RepositoryInterfaces\SupplierContactRepositoryInterface;
use Modules\Supplier\Infrastructure\Http\Resources\SupplierContactResource;

class SupplierContactController extends Controller
{
    public function __construct(
        private readonly SupplierContactRepositoryInterface $repository,
        private readonly CreateSupplierContactServiceInterface $createService,
        private readonly UpdateSupplierContactServiceInterface $updateService,
        private readonly DeleteSupplierContactServiceInterface $deleteService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $supplierId = (int) $request->query('supplier_id');
        $contacts   = $this->repository->findBySupplier($supplierId);
        return response()->json($contacts);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'supplier_id' => 'required|integer',
            'name'        => 'required|string|max:255',
            'email'       => 'sometimes|nullable|email',
            'phone'       => 'sometimes|nullable|string|max:50',
            'position'    => 'sometimes|nullable|string|max:100',
            'is_primary'  => 'sometimes|boolean',
        ]);

        $data = new SupplierContactData(
            supplierId: $validated['supplier_id'],
            name:       $validated['name'],
            email:      $validated['email'] ?? null,
            phone:      $validated['phone'] ?? null,
            position:   $validated['position'] ?? null,
            isPrimary:  $validated['is_primary'] ?? false,
        );

        $contact = $this->createService->execute($data);
        return response()->json(new SupplierContactResource($contact), 201);
    }

    public function show(int $id): JsonResponse
    {
        $contact = $this->repository->findById($id);
        if (!$contact) {
            return response()->json(['message' => 'Not found'], 404);
        }
        return response()->json(new SupplierContactResource($contact));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'supplier_id' => 'sometimes|integer',
            'name'        => 'sometimes|string|max:255',
            'email'       => 'sometimes|nullable|email',
            'phone'       => 'sometimes|nullable|string|max:50',
            'position'    => 'sometimes|nullable|string|max:100',
            'is_primary'  => 'sometimes|boolean',
        ]);

        $existing = $this->repository->findById($id);
        if (!$existing) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $data = new SupplierContactData(
            supplierId: $validated['supplier_id'] ?? $existing->supplierId,
            name:       $validated['name'] ?? $existing->name,
            email:      $validated['email'] ?? $existing->email,
            phone:      $validated['phone'] ?? $existing->phone,
            position:   $validated['position'] ?? $existing->position,
            isPrimary:  $validated['is_primary'] ?? $existing->isPrimary,
        );

        $updated = $this->updateService->execute($id, $data);
        return response()->json(new SupplierContactResource($updated));
    }

    public function destroy(int $id): JsonResponse
    {
        $contact = $this->repository->findById($id);
        if (!$contact) {
            return response()->json(['message' => 'Not found'], 404);
        }
        $this->deleteService->execute($id);
        return response()->json(null, 204);
    }
}
