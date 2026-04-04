<?php
namespace Modules\Customer\Infrastructure\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Customer\Application\Contracts\CreateCustomerAddressServiceInterface;
use Modules\Customer\Application\Contracts\DeleteCustomerAddressServiceInterface;
use Modules\Customer\Application\Contracts\UpdateCustomerAddressServiceInterface;
use Modules\Customer\Application\DTOs\CustomerAddressData;
use Modules\Customer\Domain\RepositoryInterfaces\CustomerAddressRepositoryInterface;
use Modules\Customer\Infrastructure\Http\Resources\CustomerAddressResource;

class CustomerAddressController extends Controller
{
    public function __construct(
        private readonly CustomerAddressRepositoryInterface $repository,
        private readonly CreateCustomerAddressServiceInterface $createService,
        private readonly UpdateCustomerAddressServiceInterface $updateService,
        private readonly DeleteCustomerAddressServiceInterface $deleteService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $customerId = (int) $request->query('customer_id');
        $addresses  = $this->repository->findByCustomer($customerId);
        return response()->json($addresses);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id'  => 'required|integer',
            'address_type' => 'sometimes|string|max:20',
            'street'       => 'required|string',
            'city'         => 'sometimes|nullable|string|max:100',
            'state'        => 'sometimes|nullable|string|max:100',
            'country'      => 'sometimes|nullable|string|max:100',
            'postal_code'  => 'sometimes|nullable|string|max:20',
            'is_default'   => 'sometimes|boolean',
        ]);

        $data = new CustomerAddressData(
            customerId:  $validated['customer_id'],
            addressType: $validated['address_type'] ?? 'billing',
            street:      $validated['street'],
            city:        $validated['city'] ?? null,
            state:       $validated['state'] ?? null,
            country:     $validated['country'] ?? null,
            postalCode:  $validated['postal_code'] ?? null,
            isDefault:   $validated['is_default'] ?? false,
        );

        $address = $this->createService->execute($data);
        return response()->json(new CustomerAddressResource($address), 201);
    }

    public function show(int $id): JsonResponse
    {
        $address = $this->repository->findById($id);
        if (!$address) {
            return response()->json(['message' => 'Not found'], 404);
        }
        return response()->json(new CustomerAddressResource($address));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'customer_id'  => 'sometimes|integer',
            'address_type' => 'sometimes|string|max:20',
            'street'       => 'sometimes|string',
            'city'         => 'sometimes|nullable|string|max:100',
            'state'        => 'sometimes|nullable|string|max:100',
            'country'      => 'sometimes|nullable|string|max:100',
            'postal_code'  => 'sometimes|nullable|string|max:20',
            'is_default'   => 'sometimes|boolean',
        ]);

        $existing = $this->repository->findById($id);
        if (!$existing) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $data = new CustomerAddressData(
            customerId:  $validated['customer_id'] ?? $existing->customerId,
            addressType: $validated['address_type'] ?? $existing->addressType,
            street:      $validated['street'] ?? $existing->street,
            city:        $validated['city'] ?? $existing->city,
            state:       $validated['state'] ?? $existing->state,
            country:     $validated['country'] ?? $existing->country,
            postalCode:  $validated['postal_code'] ?? $existing->postalCode,
            isDefault:   $validated['is_default'] ?? $existing->isDefault,
        );

        $updated = $this->updateService->execute($id, $data);
        return response()->json(new CustomerAddressResource($updated));
    }

    public function destroy(int $id): JsonResponse
    {
        $address = $this->repository->findById($id);
        if (!$address) {
            return response()->json(['message' => 'Not found'], 404);
        }
        $this->deleteService->execute($id);
        return response()->json(null, 204);
    }
}
