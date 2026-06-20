<?php

declare(strict_types=1);

namespace Modules\Supplier\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Supplier\Http\Requests\AssignSupplierCategoryRequest;
use Modules\Supplier\Http\Requests\ListSupplierRequest;
use Modules\Supplier\Http\Requests\StoreSupplierAddressRequest;
use Modules\Supplier\Http\Requests\StoreSupplierBankAccountRequest;
use Modules\Supplier\Http\Requests\StoreSupplierContactRequest;
use Modules\Supplier\Http\Requests\StoreSupplierDocumentRequest;
use Modules\Supplier\Http\Requests\StoreSupplierItemMappingRequest;
use Modules\Supplier\Http\Requests\UpdateSupplierAddressRequest;
use Modules\Supplier\Http\Requests\UpdateSupplierBankAccountRequest;
use Modules\Supplier\Http\Requests\UpdateSupplierContactRequest;
use Modules\Supplier\Http\Requests\UpdateSupplierCreditProfileRequest;
use Modules\Supplier\Http\Requests\UpdateSupplierDocumentRequest;
use Modules\Supplier\Http\Requests\UpdateSupplierItemMappingRequest;
use Modules\Supplier\Http\Resources\SupplierAddressResource;
use Modules\Supplier\Http\Resources\SupplierBankAccountResource;
use Modules\Supplier\Http\Resources\SupplierCategoryResource;
use Modules\Supplier\Http\Resources\SupplierContactResource;
use Modules\Supplier\Http\Resources\SupplierCreditProfileResource;
use Modules\Supplier\Http\Resources\SupplierDocumentResource;
use Modules\Supplier\Http\Resources\SupplierItemMappingResource;
use Modules\Supplier\Http\Resources\SupplierStatusHistoryResource;
use Modules\Supplier\Models\Supplier;
use Modules\Supplier\Services\SupplierAddressService;
use Modules\Supplier\Services\SupplierAuthorizationService;
use Modules\Supplier\Services\SupplierBankAccountService;
use Modules\Supplier\Services\SupplierCategoryService;
use Modules\Supplier\Services\SupplierContactService;
use Modules\Supplier\Services\SupplierCreditProfileService;
use Modules\Supplier\Services\SupplierDocumentService;
use Modules\Supplier\Services\SupplierItemMappingService;
use Modules\Supplier\Services\SupplierQueryService;
use Modules\Supplier\Services\SupplierRelationQueryService;

final class SupplierRelationController
{
    public function __construct(
        private readonly SupplierQueryService $suppliers,
        private readonly SupplierRelationQueryService $queries,
        private readonly SupplierContactService $contactsService,
        private readonly SupplierAddressService $addressesService,
        private readonly SupplierBankAccountService $bankAccountsService,
        private readonly SupplierCategoryService $categoriesService,
        private readonly SupplierDocumentService $documentsService,
        private readonly SupplierItemMappingService $itemMappingsService,
        private readonly SupplierCreditProfileService $creditProfilesService,
        private readonly SupplierAuthorizationService $authorization,
    ) {}

    public function contacts(ListSupplierRequest $request, int $supplier): AnonymousResourceCollection
    {
        return SupplierContactResource::collection($this->queries->contacts($this->supplier($request, $supplier), $request->perPage()));
    }

    public function storeContact(StoreSupplierContactRequest $request, int $supplier): JsonResponse
    {
        return $this->created(new SupplierContactResource($this->contactsService->create(
            $this->supplier($request, $supplier),
            $request->toData(),
        )));
    }

    public function updateContact(UpdateSupplierContactRequest $request, int $supplier, int $contact): SupplierContactResource
    {
        $parent = $this->supplier($request, $supplier);

        return new SupplierContactResource($this->contactsService->update(
            $parent,
            $this->queries->contact($parent, $contact),
            $request->toData(),
        ));
    }

    public function deleteContact(ListSupplierRequest $request, int $supplier, int $contact): JsonResponse
    {
        $parent = $this->supplier($request, $supplier);
        $this->contactsService->delete($parent, $this->queries->contact($parent, $contact));

        return response()->json(null, 204);
    }

    public function addresses(ListSupplierRequest $request, int $supplier): AnonymousResourceCollection
    {
        return SupplierAddressResource::collection($this->queries->addresses($this->supplier($request, $supplier), $request->perPage()));
    }

    public function storeAddress(StoreSupplierAddressRequest $request, int $supplier): JsonResponse
    {
        return $this->created(new SupplierAddressResource($this->addressesService->create(
            $this->supplier($request, $supplier),
            $request->toData(),
        )));
    }

    public function updateAddress(UpdateSupplierAddressRequest $request, int $supplier, int $address): SupplierAddressResource
    {
        $parent = $this->supplier($request, $supplier);

        return new SupplierAddressResource($this->addressesService->update(
            $parent,
            $this->queries->address($parent, $address),
            $request->toData(),
        ));
    }

    public function deleteAddress(ListSupplierRequest $request, int $supplier, int $address): JsonResponse
    {
        $parent = $this->supplier($request, $supplier);
        $this->addressesService->delete($parent, $this->queries->address($parent, $address));

        return response()->json(null, 204);
    }

    public function bankAccounts(ListSupplierRequest $request, int $supplier): AnonymousResourceCollection
    {
        return SupplierBankAccountResource::collection($this->queries->bankAccounts($this->supplier($request, $supplier), $request->perPage()));
    }

    public function storeBankAccount(StoreSupplierBankAccountRequest $request, int $supplier): JsonResponse
    {
        $account = $this->bankAccountsService->create($this->supplier($request, $supplier), $request->toData())->load('currency');

        return $this->created(new SupplierBankAccountResource($account));
    }

    public function updateBankAccount(UpdateSupplierBankAccountRequest $request, int $supplier, int $bankAccount): SupplierBankAccountResource
    {
        $parent = $this->supplier($request, $supplier);

        return new SupplierBankAccountResource($this->bankAccountsService->update(
            $parent,
            $this->queries->bankAccount($parent, $bankAccount),
            $request->toData(),
        ));
    }

    public function deleteBankAccount(ListSupplierRequest $request, int $supplier, int $bankAccount): JsonResponse
    {
        $parent = $this->supplier($request, $supplier);
        $this->bankAccountsService->delete($parent, $this->queries->bankAccount($parent, $bankAccount));

        return response()->json(null, 204);
    }

    public function categories(ListSupplierRequest $request, int $supplier): AnonymousResourceCollection
    {
        return SupplierCategoryResource::collection($this->queries->categories($this->supplier($request, $supplier), $request->perPage()));
    }

    public function assignCategory(AssignSupplierCategoryRequest $request, int $supplier): JsonResponse
    {
        return $this->created(new SupplierCategoryResource($this->categoriesService->attach(
            $this->supplier($request, $supplier),
            $request->categoryId(),
        )));
    }

    public function deleteCategory(ListSupplierRequest $request, int $supplier, int $category): JsonResponse
    {
        $this->categoriesService->detach($this->supplier($request, $supplier), $category);

        return response()->json(null, 204);
    }

    public function documents(ListSupplierRequest $request, int $supplier): AnonymousResourceCollection
    {
        return SupplierDocumentResource::collection($this->queries->documents($this->supplier($request, $supplier), $request->perPage()));
    }

    public function storeDocument(StoreSupplierDocumentRequest $request, int $supplier): JsonResponse
    {
        return $this->created(new SupplierDocumentResource($this->documentsService->create(
            $this->supplier($request, $supplier),
            $request->toData(),
        )));
    }

    public function updateDocument(UpdateSupplierDocumentRequest $request, int $supplier, int $document): SupplierDocumentResource
    {
        $parent = $this->supplier($request, $supplier);

        return new SupplierDocumentResource($this->documentsService->update(
            $parent,
            $this->queries->document($parent, $document),
            $request->toData(),
        ));
    }

    public function deleteDocument(ListSupplierRequest $request, int $supplier, int $document): JsonResponse
    {
        $parent = $this->supplier($request, $supplier);
        $this->documentsService->delete($parent, $this->queries->document($parent, $document));

        return response()->json(null, 204);
    }

    public function itemMappings(ListSupplierRequest $request, int $supplier): AnonymousResourceCollection
    {
        return SupplierItemMappingResource::collection($this->queries->itemMappings($this->supplier($request, $supplier), $request->perPage()));
    }

    public function storeItemMapping(StoreSupplierItemMappingRequest $request, int $supplier): JsonResponse
    {
        $mapping = $this->itemMappingsService->create($this->supplier($request, $supplier), $request->toData())
            ->load(['item.category', 'item.brand', 'variant', 'defaultPurchaseUom']);

        return $this->created(new SupplierItemMappingResource($mapping));
    }

    public function updateItemMapping(UpdateSupplierItemMappingRequest $request, int $supplier, int $mapping): SupplierItemMappingResource
    {
        $parent = $this->supplier($request, $supplier);

        return new SupplierItemMappingResource($this->itemMappingsService->update(
            $parent,
            $this->queries->itemMapping($parent, $mapping),
            $request->toData(),
        ));
    }

    public function deleteItemMapping(ListSupplierRequest $request, int $supplier, int $mapping): JsonResponse
    {
        $parent = $this->supplier($request, $supplier);
        $this->itemMappingsService->delete($parent, $this->queries->itemMapping($parent, $mapping));

        return response()->json(null, 204);
    }

    public function creditProfile(ListSupplierRequest $request, int $supplier): SupplierCreditProfileResource|JsonResponse
    {
        $profile = $this->creditProfilesService->get($this->supplier($request, $supplier));

        return $profile ? new SupplierCreditProfileResource($profile) : response()->json(['data' => null]);
    }

    public function updateCreditProfile(UpdateSupplierCreditProfileRequest $request, int $supplier): SupplierCreditProfileResource
    {
        return new SupplierCreditProfileResource($this->creditProfilesService->set(
            $this->supplier($request, $supplier),
            $request->toData(),
        ));
    }

    public function statusHistory(ListSupplierRequest $request, int $supplier): AnonymousResourceCollection
    {
        return SupplierStatusHistoryResource::collection($this->queries->statusHistory(
            $this->supplier($request, $supplier),
            $request->perPage(),
        ));
    }

    private function supplier(TenantScopedRequest $request, int $supplier): Supplier
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), $request->isMethodSafe() ? SupplierAuthorizationService::VIEW : SupplierAuthorizationService::UPDATE);

        return $this->suppliers->supplier($supplier, $request->tenantId(), $request->organizationUnitId());
    }

    private function created(JsonResource $resource): JsonResponse
    {
        return $resource->response()->setStatusCode(201);
    }
}
