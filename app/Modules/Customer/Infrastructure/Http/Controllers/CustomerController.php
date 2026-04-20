<?php

declare(strict_types=1);

namespace Modules\Customer\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Customer\Application\Contracts\CreateCustomerServiceInterface;
use Modules\Customer\Application\Contracts\DeleteCustomerServiceInterface;
use Modules\Customer\Application\Contracts\FindCustomerServiceInterface;
use Modules\Customer\Application\Contracts\UpdateCustomerServiceInterface;
use Modules\Customer\Domain\Entities\Customer;
use Modules\Customer\Infrastructure\Http\Requests\ListCustomerRequest;
use Modules\Customer\Infrastructure\Http\Requests\StoreCustomerRequest;
use Modules\Customer\Infrastructure\Http\Requests\UpdateCustomerRequest;
use Modules\Customer\Infrastructure\Http\Resources\CustomerCollection;
use Modules\Customer\Infrastructure\Http\Resources\CustomerResource;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CustomerController extends AuthorizedController
{
    public function __construct(
        protected CreateCustomerServiceInterface $createCustomerService,
        protected UpdateCustomerServiceInterface $updateCustomerService,
        protected DeleteCustomerServiceInterface $deleteCustomerService,
        protected FindCustomerServiceInterface $findCustomerService,
    ) {}

    public function index(ListCustomerRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Customer::class);
        $validated = $request->validated();

        $filters = array_filter([
            'tenant_id' => $validated['tenant_id'] ?? null,
            'user_id' => $validated['user_id'] ?? null,
            'org_unit_id' => $validated['org_unit_id'] ?? null,
            'customer_code' => $validated['customer_code'] ?? null,
            'name' => $validated['name'] ?? null,
            'type' => $validated['type'] ?? null,
            'status' => $validated['status'] ?? null,
            'currency_id' => $validated['currency_id'] ?? null,
            'ar_account_id' => $validated['ar_account_id'] ?? null,
        ], static fn (mixed $value): bool => $value !== null && $value !== '');

        $customers = $this->findCustomerService->list(
            filters: $filters,
            perPage: (int) ($validated['per_page'] ?? 15),
            page: (int) ($validated['page'] ?? 1),
            sort: $validated['sort'] ?? null,
            include: $validated['include'] ?? null,
        );

        return (new CustomerCollection($customers))->response();
    }

    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $this->authorize('create', Customer::class);

        $payload = $request->validated();
        $avatarFile = $request->file('user.avatar');

        if ($avatarFile !== null) {
            $payload['user'] ??= [];
            $payload['user']['avatar'] = $avatarFile;
        }

        $customer = $this->createCustomerService->execute($payload);

        return (new CustomerResource($customer))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function show(Request $request, int $customer): CustomerResource
    {
        $foundCustomer = $this->findCustomerOrFail($customer);
        $this->authorize('view', $foundCustomer);

        return new CustomerResource($foundCustomer);
    }

    public function update(UpdateCustomerRequest $request, int $customer): CustomerResource
    {
        $foundCustomer = $this->findCustomerOrFail($customer);
        $this->authorize('update', $foundCustomer);

        $payload = $request->validated();
        $avatarFile = $request->file('user.avatar');
        if ($avatarFile !== null) {
            $payload['user'] ??= [];
            $payload['user']['avatar'] = $avatarFile;
        }
        $payload['id'] = $customer;

        return new CustomerResource($this->updateCustomerService->execute($payload));
    }

    public function destroy(int $customer): JsonResponse
    {
        $foundCustomer = $this->findCustomerOrFail($customer);
        $this->authorize('delete', $foundCustomer);

        $this->deleteCustomerService->execute(['id' => $customer]);

        return Response::json(['message' => 'Customer deleted successfully']);
    }

    private function findCustomerOrFail(int $customerId): Customer
    {
        $customer = $this->findCustomerService->find($customerId);

        if (! $customer) {
            throw new NotFoundHttpException('Customer not found.');
        }

        return $customer;
    }
}
