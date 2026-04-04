<?php
namespace Modules\Customer\Infrastructure\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Customer\Application\Contracts\CreateCustomerServiceInterface;
use Modules\Customer\Application\Contracts\DeleteCustomerServiceInterface;
use Modules\Customer\Application\Contracts\UpdateCustomerServiceInterface;
use Modules\Customer\Application\DTOs\CustomerData;
use Modules\Customer\Domain\RepositoryInterfaces\CustomerRepositoryInterface;
use Modules\Customer\Infrastructure\Http\Resources\CustomerResource;

class CustomerController extends Controller
{
    public function __construct(
        private readonly CustomerRepositoryInterface $repository,
        private readonly CreateCustomerServiceInterface $createService,
        private readonly UpdateCustomerServiceInterface $updateService,
        private readonly DeleteCustomerServiceInterface $deleteService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId  = (int) $request->query('tenant_id');
        $filters   = $request->only(['status', 'code']);
        $customers = $this->repository->findAll($tenantId, $filters);
        return response()->json($customers);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id'    => 'required|integer',
            'name'         => 'required|string|max:255',
            'code'         => 'required|string|max:50',
            'status'       => 'sometimes|string|in:active,inactive,suspended',
            'email'        => 'sometimes|nullable|email',
            'phone'        => 'sometimes|nullable|string|max:50',
            'tax_number'   => 'sometimes|nullable|string|max:100',
            'currency'     => 'sometimes|nullable|string|max:3',
            'credit_limit' => 'sometimes|nullable|numeric|min:0',
            'notes'        => 'sometimes|nullable|string',
        ]);

        $data = new CustomerData(
            tenantId:    $validated['tenant_id'],
            name:        $validated['name'],
            code:        $validated['code'],
            status:      $validated['status'] ?? 'active',
            email:       $validated['email'] ?? null,
            phone:       $validated['phone'] ?? null,
            taxNumber:   $validated['tax_number'] ?? null,
            currency:    $validated['currency'] ?? 'USD',
            creditLimit: isset($validated['credit_limit']) ? (float) $validated['credit_limit'] : null,
            notes:       $validated['notes'] ?? null,
        );

        $customer = $this->createService->execute($data);
        return response()->json(new CustomerResource($customer), 201);
    }

    public function show(int $id): JsonResponse
    {
        $customer = $this->repository->findById($id);
        if (!$customer) {
            return response()->json(['message' => 'Not found'], 404);
        }
        return response()->json(new CustomerResource($customer));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id'    => 'sometimes|integer',
            'name'         => 'sometimes|string|max:255',
            'code'         => 'sometimes|string|max:50',
            'status'       => 'sometimes|string|in:active,inactive,suspended',
            'email'        => 'sometimes|nullable|email',
            'phone'        => 'sometimes|nullable|string|max:50',
            'tax_number'   => 'sometimes|nullable|string|max:100',
            'currency'     => 'sometimes|nullable|string|max:3',
            'credit_limit' => 'sometimes|nullable|numeric|min:0',
            'notes'        => 'sometimes|nullable|string',
        ]);

        $existing = $this->repository->findById($id);
        if (!$existing) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $data = new CustomerData(
            tenantId:    $validated['tenant_id'] ?? $existing->tenantId,
            name:        $validated['name'] ?? $existing->name,
            code:        $validated['code'] ?? $existing->code,
            status:      $validated['status'] ?? $existing->status,
            email:       $validated['email'] ?? $existing->email,
            phone:       $validated['phone'] ?? $existing->phone,
            taxNumber:   $validated['tax_number'] ?? $existing->taxNumber,
            currency:    $validated['currency'] ?? $existing->currency,
            creditLimit: isset($validated['credit_limit']) ? (float) $validated['credit_limit'] : $existing->creditLimit,
            notes:       $validated['notes'] ?? $existing->notes,
        );

        $updated = $this->updateService->execute($id, $data);
        return response()->json(new CustomerResource($updated));
    }

    public function destroy(int $id): JsonResponse
    {
        $customer = $this->repository->findById($id);
        if (!$customer) {
            return response()->json(['message' => 'Not found'], 404);
        }
        $this->deleteService->execute($id);
        return response()->json(null, 204);
    }
}
