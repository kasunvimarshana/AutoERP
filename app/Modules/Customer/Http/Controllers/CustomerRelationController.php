<?php

declare(strict_types=1);

namespace Modules\Customer\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Customer\Http\Requests\AssignCustomerCategoryRequest;
use Modules\Customer\Http\Requests\ListCustomerRequest;
use Modules\Customer\Http\Requests\StoreCustomerAddressRequest;
use Modules\Customer\Http\Requests\StoreCustomerBankAccountRequest;
use Modules\Customer\Http\Requests\StoreCustomerContactRequest;
use Modules\Customer\Http\Requests\StoreCustomerDocumentRequest;
use Modules\Customer\Http\Requests\UpdateCustomerAddressRequest;
use Modules\Customer\Http\Requests\UpdateCustomerBankAccountRequest;
use Modules\Customer\Http\Requests\UpdateCustomerContactRequest;
use Modules\Customer\Http\Requests\UpdateCustomerCreditProfileRequest;
use Modules\Customer\Http\Requests\UpdateCustomerDocumentRequest;
use Modules\Customer\Http\Resources\CustomerAddressResource;
use Modules\Customer\Http\Resources\CustomerBankAccountResource;
use Modules\Customer\Http\Resources\CustomerCategoryResource;
use Modules\Customer\Http\Resources\CustomerContactResource;
use Modules\Customer\Http\Resources\CustomerCreditProfileResource;
use Modules\Customer\Http\Resources\CustomerDocumentResource;
use Modules\Customer\Http\Resources\CustomerStatusHistoryResource;
use Modules\Customer\Models\Customer;
use Modules\Customer\Services\CustomerAddressService;
use Modules\Customer\Services\CustomerAuthorizationService;
use Modules\Customer\Services\CustomerBankAccountService;
use Modules\Customer\Services\CustomerCategoryService;
use Modules\Customer\Services\CustomerContactService;
use Modules\Customer\Services\CustomerCreditProfileService;
use Modules\Customer\Services\CustomerDocumentService;
use Modules\Customer\Services\CustomerQueryService;
use Modules\Customer\Services\CustomerRelationQueryService;

final class CustomerRelationController
{
    public function __construct(
        private readonly CustomerQueryService $customers,
        private readonly CustomerRelationQueryService $queries,
        private readonly CustomerContactService $contactsService,
        private readonly CustomerAddressService $addressesService,
        private readonly CustomerBankAccountService $bankAccountsService,
        private readonly CustomerCategoryService $categoriesService,
        private readonly CustomerDocumentService $documentsService,
        private readonly CustomerCreditProfileService $creditProfilesService,
        private readonly CustomerAuthorizationService $authorization,
    ) {}

    public function contacts(ListCustomerRequest $request, int $customer): AnonymousResourceCollection
    {
        return CustomerContactResource::collection($this->queries->contacts($this->customer($request, $customer), $request->perPage()));
    }

    public function storeContact(StoreCustomerContactRequest $request, int $customer): JsonResponse
    {
        return $this->created(new CustomerContactResource($this->contactsService->create(
            $this->customer($request, $customer),
            $request->toData(),
        )));
    }

    public function updateContact(UpdateCustomerContactRequest $request, int $customer, int $contact): CustomerContactResource
    {
        $parent = $this->customer($request, $customer);

        return new CustomerContactResource($this->contactsService->update(
            $parent,
            $this->queries->contact($parent, $contact),
            $request->toData(),
        ));
    }

    public function deleteContact(ListCustomerRequest $request, int $customer, int $contact): JsonResponse
    {
        $parent = $this->customer($request, $customer);
        $this->contactsService->delete($parent, $this->queries->contact($parent, $contact));

        return response()->json(null, 204);
    }

    public function addresses(ListCustomerRequest $request, int $customer): AnonymousResourceCollection
    {
        return CustomerAddressResource::collection($this->queries->addresses($this->customer($request, $customer), $request->perPage()));
    }

    public function storeAddress(StoreCustomerAddressRequest $request, int $customer): JsonResponse
    {
        return $this->created(new CustomerAddressResource($this->addressesService->create(
            $this->customer($request, $customer),
            $request->toData(),
        )));
    }

    public function updateAddress(UpdateCustomerAddressRequest $request, int $customer, int $address): CustomerAddressResource
    {
        $parent = $this->customer($request, $customer);

        return new CustomerAddressResource($this->addressesService->update(
            $parent,
            $this->queries->address($parent, $address),
            $request->toData(),
        ));
    }

    public function deleteAddress(ListCustomerRequest $request, int $customer, int $address): JsonResponse
    {
        $parent = $this->customer($request, $customer);
        $this->addressesService->delete($parent, $this->queries->address($parent, $address));

        return response()->json(null, 204);
    }

    public function bankAccounts(ListCustomerRequest $request, int $customer): AnonymousResourceCollection
    {
        return CustomerBankAccountResource::collection($this->queries->bankAccounts($this->customer($request, $customer), $request->perPage()));
    }

    public function storeBankAccount(StoreCustomerBankAccountRequest $request, int $customer): JsonResponse
    {
        $account = $this->bankAccountsService->create($this->customer($request, $customer), $request->toData())->load('currency');

        return $this->created(new CustomerBankAccountResource($account));
    }

    public function updateBankAccount(UpdateCustomerBankAccountRequest $request, int $customer, int $bankAccount): CustomerBankAccountResource
    {
        $parent = $this->customer($request, $customer);

        return new CustomerBankAccountResource($this->bankAccountsService->update(
            $parent,
            $this->queries->bankAccount($parent, $bankAccount),
            $request->toData(),
        ));
    }

    public function deleteBankAccount(ListCustomerRequest $request, int $customer, int $bankAccount): JsonResponse
    {
        $parent = $this->customer($request, $customer);
        $this->bankAccountsService->delete($parent, $this->queries->bankAccount($parent, $bankAccount));

        return response()->json(null, 204);
    }

    public function categories(ListCustomerRequest $request, int $customer): AnonymousResourceCollection
    {
        return CustomerCategoryResource::collection($this->queries->categories($this->customer($request, $customer), $request->perPage()));
    }

    public function assignCategory(AssignCustomerCategoryRequest $request, int $customer): JsonResponse
    {
        return $this->created(new CustomerCategoryResource($this->categoriesService->attach(
            $this->customer($request, $customer),
            $request->categoryId(),
        )));
    }

    public function deleteCategory(ListCustomerRequest $request, int $customer, int $category): JsonResponse
    {
        $this->categoriesService->detach($this->customer($request, $customer), $category);

        return response()->json(null, 204);
    }

    public function documents(ListCustomerRequest $request, int $customer): AnonymousResourceCollection
    {
        return CustomerDocumentResource::collection($this->queries->documents($this->customer($request, $customer), $request->perPage()));
    }

    public function storeDocument(StoreCustomerDocumentRequest $request, int $customer): JsonResponse
    {
        return $this->created(new CustomerDocumentResource($this->documentsService->create(
            $this->customer($request, $customer),
            $request->toData(),
        )));
    }

    public function updateDocument(UpdateCustomerDocumentRequest $request, int $customer, int $document): CustomerDocumentResource
    {
        $parent = $this->customer($request, $customer);

        return new CustomerDocumentResource($this->documentsService->update(
            $parent,
            $this->queries->document($parent, $document),
            $request->toData(),
        ));
    }

    public function deleteDocument(ListCustomerRequest $request, int $customer, int $document): JsonResponse
    {
        $parent = $this->customer($request, $customer);
        $this->documentsService->delete($parent, $this->queries->document($parent, $document));

        return response()->json(null, 204);
    }

    public function creditProfile(ListCustomerRequest $request, int $customer): CustomerCreditProfileResource|JsonResponse
    {
        $profile = $this->creditProfilesService->get($this->customer($request, $customer));

        return $profile ? new CustomerCreditProfileResource($profile) : response()->json(['data' => null]);
    }

    public function updateCreditProfile(UpdateCustomerCreditProfileRequest $request, int $customer): CustomerCreditProfileResource
    {
        return new CustomerCreditProfileResource($this->creditProfilesService->set(
            $this->customer($request, $customer),
            $request->toData(),
        ));
    }

    public function statusHistory(ListCustomerRequest $request, int $customer): AnonymousResourceCollection
    {
        return CustomerStatusHistoryResource::collection($this->queries->statusHistory(
            $this->customer($request, $customer),
            $request->perPage(),
        ));
    }

    private function customer(TenantScopedRequest $request, int $customer): Customer
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), $request->isMethodSafe() ? CustomerAuthorizationService::VIEW : CustomerAuthorizationService::UPDATE);

        return $this->customers->customer($customer, $request->tenantId(), $request->organizationUnitId());
    }

    private function created(JsonResource $resource): JsonResponse
    {
        return $resource->response()->setStatusCode(201);
    }
}
