<?php

declare(strict_types=1);

namespace Modules\Customer\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Customer\Application\Contracts\CreateCustomerContactServiceInterface;
use Modules\Customer\Application\Contracts\DeleteCustomerContactServiceInterface;
use Modules\Customer\Application\Contracts\FindCustomerContactServiceInterface;
use Modules\Customer\Application\Contracts\FindCustomerServiceInterface;
use Modules\Customer\Application\Contracts\UpdateCustomerContactServiceInterface;
use Modules\Customer\Domain\Entities\Customer;
use Modules\Customer\Domain\Entities\CustomerContact;
use Modules\Customer\Infrastructure\Http\Requests\ListCustomerContactRequest;
use Modules\Customer\Infrastructure\Http\Requests\StoreCustomerContactRequest;
use Modules\Customer\Infrastructure\Http\Requests\UpdateCustomerContactRequest;
use Modules\Customer\Infrastructure\Http\Resources\CustomerContactCollection;
use Modules\Customer\Infrastructure\Http\Resources\CustomerContactResource;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CustomerContactController extends AuthorizedController
{
    public function __construct(
        protected FindCustomerServiceInterface $findCustomerService,
        protected FindCustomerContactServiceInterface $findCustomerContactService,
        protected CreateCustomerContactServiceInterface $createCustomerContactService,
        protected UpdateCustomerContactServiceInterface $updateCustomerContactService,
        protected DeleteCustomerContactServiceInterface $deleteCustomerContactService,
    ) {}

    public function index(int $customerId, ListCustomerContactRequest $request): CustomerContactCollection
    {
        $customer = $this->findCustomerOrFail($customerId);
        $this->authorize('view', $customer);

        $validated = $request->validated();
        $contacts = $this->findCustomerContactService->paginateByCustomer(
            tenantId: $customer->getTenantId(),
            customerId: $customerId,
            perPage: (int) ($validated['per_page'] ?? 15),
            page: (int) ($validated['page'] ?? 1),
        );

        return new CustomerContactCollection($contacts);
    }

    public function store(StoreCustomerContactRequest $request, int $customerId): JsonResponse
    {
        $customer = $this->findCustomerOrFail($customerId);
        $this->authorize('update', $customer);

        $payload = $request->validated();
        $payload['customer_id'] = $customerId;
        $contact = $this->createCustomerContactService->execute($payload);

        return (new CustomerContactResource($contact))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function update(UpdateCustomerContactRequest $request, int $customerId, int $contactId): CustomerContactResource
    {
        $customer = $this->findCustomerOrFail($customerId);
        $this->authorize('update', $customer);

        $contact = $this->findContactOrFail($contactId, $customerId);
        $payload = $request->validated();
        $payload['customer_id'] = $customerId;
        $payload['id'] = $contact->getId();

        return new CustomerContactResource($this->updateCustomerContactService->execute($payload));
    }

    public function destroy(int $customerId, int $contactId): JsonResponse
    {
        $customer = $this->findCustomerOrFail($customerId);
        $this->authorize('update', $customer);

        $contact = $this->findContactOrFail($contactId, $customerId);
        $this->deleteCustomerContactService->execute(['id' => $contact->getId()]);

        return Response::json(['message' => 'Customer contact deleted successfully']);
    }

    private function findCustomerOrFail(int $customerId): Customer
    {
        $customer = $this->findCustomerService->find($customerId);

        if (! $customer) {
            throw new NotFoundHttpException('Customer not found.');
        }

        return $customer;
    }

    private function findContactOrFail(int $contactId, int $customerId): CustomerContact
    {
        $contact = $this->findCustomerContactService->find($contactId);
        if (! $contact || $contact->getCustomerId() !== $customerId) {
            throw new NotFoundHttpException('Customer contact not found.');
        }

        return $contact;
    }
}
