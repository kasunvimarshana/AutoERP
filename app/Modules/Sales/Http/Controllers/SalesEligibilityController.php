<?php

declare(strict_types=1);

namespace Modules\Sales\Http\Controllers;

use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Invoice\Http\Resources\InvoiceResource;
use Modules\Sales\Http\Requests\ListSalesRequest;
use Modules\Sales\Http\Resources\SalesDeliveryResource;
use Modules\Sales\Http\Resources\SalesOrderResource;
use Modules\Sales\Services\SalesAuthorizationService;
use Modules\Sales\Services\SalesSourceEligibilityService;

final class SalesEligibilityController
{
    public function __construct(private readonly SalesAuthorizationService $authorization) {}

    public function allocatableSalesOrders(ListSalesRequest $request, SalesSourceEligibilityService $eligibility): AnonymousResourceCollection
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), SalesAuthorizationService::ALLOCATIONS_VIEW);

        return SalesOrderResource::collection($eligibility->allocatableSalesOrders($request->tenantId(), $request->organizationUnitId(), $request->perPage()));
    }

    public function deliverableSalesOrders(ListSalesRequest $request, SalesSourceEligibilityService $eligibility): AnonymousResourceCollection
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), SalesAuthorizationService::DELIVERIES_VIEW);

        return SalesOrderResource::collection($eligibility->deliverableSalesOrders($request->tenantId(), $request->organizationUnitId(), $request->perPage()));
    }

    public function invoiceableSalesOrders(ListSalesRequest $request, SalesSourceEligibilityService $eligibility): AnonymousResourceCollection
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), SalesAuthorizationService::CUSTOMER_INVOICES_VIEW);

        return SalesOrderResource::collection($eligibility->invoiceableSalesOrders($request->tenantId(), $request->organizationUnitId(), $request->perPage()));
    }

    public function invoiceableSalesDeliveries(ListSalesRequest $request, SalesSourceEligibilityService $eligibility): AnonymousResourceCollection
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), SalesAuthorizationService::CUSTOMER_INVOICES_VIEW);

        return SalesDeliveryResource::collection($eligibility->invoiceableSalesDeliveries($request->tenantId(), $request->organizationUnitId(), $request->perPage()));
    }

    public function returnableSalesDeliveries(ListSalesRequest $request, SalesSourceEligibilityService $eligibility): AnonymousResourceCollection
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), SalesAuthorizationService::RETURNS_VIEW);

        return SalesDeliveryResource::collection($eligibility->returnableSalesDeliveries($request->tenantId(), $request->organizationUnitId(), $request->perPage()));
    }

    public function outstandingCustomerInvoices(ListSalesRequest $request, SalesSourceEligibilityService $eligibility): AnonymousResourceCollection
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), SalesAuthorizationService::RECEIPTS_VIEW);

        return InvoiceResource::collection($eligibility->outstandingCustomerInvoices($request->tenantId(), $request->organizationUnitId(), $request->perPage()));
    }
}
