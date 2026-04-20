<?php

declare(strict_types=1);

namespace Modules\Customer\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Customer\Application\Contracts\CreateCustomerAddressServiceInterface;
use Modules\Customer\Application\Contracts\DeleteCustomerAddressServiceInterface;
use Modules\Customer\Application\Contracts\FindCustomerAddressServiceInterface;
use Modules\Customer\Application\Contracts\FindCustomerServiceInterface;
use Modules\Customer\Application\Contracts\UpdateCustomerAddressServiceInterface;
use Modules\Customer\Domain\Entities\Customer;
use Modules\Customer\Domain\Entities\CustomerAddress;
use Modules\Customer\Infrastructure\Http\Requests\ListCustomerAddressRequest;
use Modules\Customer\Infrastructure\Http\Requests\StoreCustomerAddressRequest;
use Modules\Customer\Infrastructure\Http\Requests\UpdateCustomerAddressRequest;
use Modules\Customer\Infrastructure\Http\Resources\CustomerAddressCollection;
use Modules\Customer\Infrastructure\Http\Resources\CustomerAddressResource;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CustomerAddressController extends AuthorizedController
{
    public function __construct(
        protected FindCustomerServiceInterface $findCustomerService,
        protected FindCustomerAddressServiceInterface $findCustomerAddressService,
        protected CreateCustomerAddressServiceInterface $createCustomerAddressService,
        protected UpdateCustomerAddressServiceInterface $updateCustomerAddressService,
        protected DeleteCustomerAddressServiceInterface $deleteCustomerAddressService,
    ) {}

    public function index(int $customerId, ListCustomerAddressRequest $request): CustomerAddressCollection
    {
        $customer = $this->findCustomerOrFail($customerId);
        $this->authorize('view', $customer);

        $validated = $request->validated();
        $addresses = $this->findCustomerAddressService->paginateByCustomer(
            tenantId: $customer->getTenantId(),
            customerId: $customerId,
            perPage: (int) ($validated['per_page'] ?? 15),
            page: (int) ($validated['page'] ?? 1),
        );

        return new CustomerAddressCollection($addresses);
    }

    public function store(StoreCustomerAddressRequest $request, int $customerId): JsonResponse
    {
        $customer = $this->findCustomerOrFail($customerId);
        $this->authorize('update', $customer);

        $payload = $request->validated();
        $payload['customer_id'] = $customerId;
        $address = $this->createCustomerAddressService->execute($payload);

        return (new CustomerAddressResource($address))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function update(UpdateCustomerAddressRequest $request, int $customerId, int $addressId): CustomerAddressResource
    {
        $customer = $this->findCustomerOrFail($customerId);
        $this->authorize('update', $customer);

        $address = $this->findAddressOrFail($addressId, $customerId);
        $payload = $request->validated();
        $payload['customer_id'] = $customerId;
        $payload['id'] = $address->getId();

        return new CustomerAddressResource($this->updateCustomerAddressService->execute($payload));
    }

    public function destroy(int $customerId, int $addressId): JsonResponse
    {
        $customer = $this->findCustomerOrFail($customerId);
        $this->authorize('update', $customer);

        $address = $this->findAddressOrFail($addressId, $customerId);
        $this->deleteCustomerAddressService->execute(['id' => $address->getId()]);

        return Response::json(['message' => 'Customer address deleted successfully']);
    }

    private function findCustomerOrFail(int $customerId): Customer
    {
        $customer = $this->findCustomerService->find($customerId);

        if (! $customer) {
            throw new NotFoundHttpException('Customer not found.');
        }

        return $customer;
    }

    private function findAddressOrFail(int $addressId, int $customerId): CustomerAddress
    {
        $address = $this->findCustomerAddressService->find($addressId);
        if (! $address || $address->getCustomerId() !== $customerId) {
            throw new NotFoundHttpException('Customer address not found.');
        }

        return $address;
    }
}
