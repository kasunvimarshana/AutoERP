<?php

declare(strict_types=1);

namespace Modules\Supplier\Http\Controllers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Invoice\Contracts\InvoiceBalanceProviderInterface;
use Modules\Invoice\Enums\InvoicePartyType;
use Modules\Supplier\Enums\SupplierStatus;
use Modules\Supplier\Http\Requests\ChangeSupplierStatusRequest;
use Modules\Supplier\Http\Requests\ListSupplierRequest;
use Modules\Supplier\Http\Requests\StoreSupplierRequest;
use Modules\Supplier\Http\Requests\StoreSupplierWithRelationsRequest;
use Modules\Supplier\Http\Requests\UpdateSupplierRequest;
use Modules\Supplier\Http\Resources\SupplierResource;
use Modules\Supplier\Http\Resources\SupplierSummaryResource;
use Modules\Supplier\Models\Supplier;
use Modules\Supplier\Services\SupplierAuthorizationService;
use Modules\Supplier\Services\SupplierBlockerService;
use Modules\Supplier\Services\SupplierCreationService;
use Modules\Supplier\Services\SupplierQueryService;
use Modules\Supplier\Services\SupplierStatusService;
use Modules\Supplier\Services\SupplierUpdateService;

final class SupplierController
{
    public function __construct(
        private readonly SupplierQueryService $queries,
        private readonly SupplierCreationService $creation,
        private readonly SupplierUpdateService $updates,
        private readonly SupplierStatusService $statuses,
        private readonly SupplierAuthorizationService $authorization,
        private readonly SupplierBlockerService $blockers,
        private readonly InvoiceBalanceProviderInterface $invoiceBalances,
    ) {}

    public function index(ListSupplierRequest $request): AnonymousResourceCollection
    {
        $this->authorize($request, SupplierAuthorizationService::VIEW);

        $suppliers = $this->queries->paginate(
            $request->validated(),
            $request->tenantId(),
            $request->organizationUnitId(),
            $request->perPage(),
        );
        $this->attachTotalDue($suppliers, $request->tenantId(), $request->organizationUnitId());

        return SupplierSummaryResource::collection($suppliers);
    }

    public function store(StoreSupplierRequest $request): JsonResponse
    {
        $this->authorize($request, SupplierAuthorizationService::CREATE);

        return $this->created($this->creation->create($request->toData()));
    }

    public function storeWithRelations(StoreSupplierWithRelationsRequest $request): JsonResponse
    {
        $this->authorize($request, SupplierAuthorizationService::CREATE);

        return $this->created($this->creation->create($request->toData()));
    }

    public function show(ListSupplierRequest $request, int $supplier): SupplierResource
    {
        $this->authorize($request, SupplierAuthorizationService::VIEW);

        return new SupplierResource($this->queries->find(
            $supplier,
            $request->tenantId(),
            $request->organizationUnitId(),
        ));
    }

    public function update(UpdateSupplierRequest $request, int $supplier): SupplierResource
    {
        $this->authorize($request, SupplierAuthorizationService::UPDATE);

        return new SupplierResource($this->updates->update(
            $this->queries->supplier($supplier, $request->tenantId(), $request->organizationUnitId()),
            $request->toData(),
        )->load(['defaultCurrency']));
    }

    public function destroy(ListSupplierRequest $request, int $supplier): JsonResponse
    {
        $this->authorize($request, SupplierAuthorizationService::DELETE);
        $this->blockers->delete($this->queries->supplier(
            $supplier,
            $request->tenantId(),
            $request->organizationUnitId(),
        ));

        return response()->json(null, 204);
    }

    public function activate(ListSupplierRequest $request, int $supplier): SupplierResource
    {
        $this->authorize($request, SupplierAuthorizationService::UPDATE);

        return $this->changeTo($request, $supplier, SupplierStatus::Active);
    }

    public function deactivate(ListSupplierRequest $request, int $supplier): SupplierResource
    {
        $this->authorize($request, SupplierAuthorizationService::UPDATE);

        return $this->changeTo($request, $supplier, SupplierStatus::Inactive);
    }

    public function changeStatus(ChangeSupplierStatusRequest $request, int $supplier): SupplierResource
    {
        $this->authorize($request, SupplierAuthorizationService::UPDATE);
        $model = $this->queries->supplier($supplier, $request->tenantId(), $request->organizationUnitId());

        return new SupplierResource($this->statuses->change($model, $request->toData())->load('defaultCurrency'));
    }

    public function lookup(ListSupplierRequest $request, ?string $kind = null): AnonymousResourceCollection
    {
        $this->authorize($request, SupplierAuthorizationService::VIEW);

        return SupplierSummaryResource::collection($this->queries->lookup(
            $request->validated(),
            $request->tenantId(),
            $request->organizationUnitId(),
            $request->perPage(),
            $kind ?? 'all',
        ));
    }

    private function changeTo(ListSupplierRequest $request, int $supplier, SupplierStatus $status): SupplierResource
    {
        $model = $this->queries->supplier($supplier, $request->tenantId(), $request->organizationUnitId());

        return new SupplierResource($this->statuses->changeTo(
            $model,
            $status,
            $request->currentUserId(),
        )->load('defaultCurrency'));
    }

    private function created(Supplier $supplier): JsonResponse
    {
        return (new SupplierResource($supplier))->response()->setStatusCode(201);
    }

    private function attachTotalDue(
        LengthAwarePaginator $suppliers,
        int $tenantId,
        ?int $organizationUnitId,
    ): void {
        $supplierIds = $suppliers->getCollection()
            ->map(static fn (Supplier $supplier): int => (int) $supplier->getKey())
            ->all();
        $totals = $this->invoiceBalances->getOutstandingTotalsForParties(
            $tenantId,
            $organizationUnitId,
            InvoicePartyType::Supplier->value,
            $supplierIds,
        );

        $suppliers->getCollection()->each(static function (Supplier $supplier) use ($totals): void {
            $supplier->setAttribute('total_due', $totals[(int) $supplier->getKey()] ?? [[
                'amount' => '0.000000',
                'currency_code' => $supplier->defaultCurrency?->code,
            ]]);
        });
    }

    private function authorize(ListSupplierRequest|StoreSupplierRequest|StoreSupplierWithRelationsRequest|UpdateSupplierRequest|ChangeSupplierStatusRequest $request, string $permission): void
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), $permission);
    }
}
