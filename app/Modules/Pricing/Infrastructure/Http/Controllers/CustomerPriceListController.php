<?php

declare(strict_types=1);

namespace Modules\Pricing\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Pricing\Application\Contracts\CreateCustomerPriceListServiceInterface;
use Modules\Pricing\Domain\Entities\CustomerPriceList;
use Modules\Pricing\Application\Contracts\DeleteCustomerPriceListServiceInterface;
use Modules\Pricing\Application\Contracts\FindCustomerPriceListServiceInterface;
use Modules\Pricing\Infrastructure\Http\Requests\ListAssignmentRequest;
use Modules\Pricing\Infrastructure\Http\Requests\StoreCustomerPriceListRequest;
use Modules\Pricing\Infrastructure\Http\Resources\CustomerPriceListCollection;
use Modules\Pricing\Infrastructure\Http\Resources\CustomerPriceListResource;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CustomerPriceListController extends AuthorizedController
{
    public function __construct(
        protected CreateCustomerPriceListServiceInterface $createCustomerPriceListService,
        protected FindCustomerPriceListServiceInterface $findCustomerPriceListService,
        protected DeleteCustomerPriceListServiceInterface $deleteCustomerPriceListService,
    ) {}

    public function index(int $customer, ListAssignmentRequest $request): CustomerPriceListCollection
    {
        $this->authorize('viewAny', CustomerPriceList::class);
        $validated = $request->validated();

        $assignments = $this->findCustomerPriceListService->paginateByCustomer(
            tenantId: (int) $request->input('tenant_id', $request->header('X-Tenant-ID')),
            customerId: $customer,
            perPage: (int) ($validated['per_page'] ?? 15),
            page: (int) ($validated['page'] ?? 1),
        );

        return new CustomerPriceListCollection($assignments);
    }

    public function store(StoreCustomerPriceListRequest $request, int $customer): JsonResponse
    {
        $this->authorize('create', CustomerPriceList::class);
        $payload = $request->validated();
        $payload['customer_id'] = $customer;

        $assignment = $this->createCustomerPriceListService->execute($payload);

        return (new CustomerPriceListResource($assignment))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function destroy(int $customer, int $assignment): JsonResponse
    {
        $foundAssignment = $this->findCustomerPriceListService->find($assignment);

        if (! $foundAssignment || $foundAssignment->getCustomerId() !== $customer) {
            throw new NotFoundHttpException('Customer price list assignment not found.');
        }

        $this->authorize('delete', $foundAssignment);
        $this->deleteCustomerPriceListService->execute(['id' => $assignment]);

        return Response::json(['message' => 'Customer price list assignment deleted successfully']);
    }
}
