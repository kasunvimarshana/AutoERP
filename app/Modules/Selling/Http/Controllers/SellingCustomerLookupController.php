<?php

declare(strict_types=1);

namespace Modules\Selling\Http\Controllers;

use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Customer\Http\Resources\CustomerSummaryResource;
use Modules\Customer\Services\CustomerQueryService;
use Modules\Selling\Http\Requests\SaleQueryRequest;

final class SellingCustomerLookupController
{
    public function index(SaleQueryRequest $request, CustomerQueryService $customers): AnonymousResourceCollection
    {
        return CustomerSummaryResource::collection($customers->lookup(
            $request->validated(),
            $request->tenantId(),
            $request->organizationUnitId(),
            (int) $request->validated('per_page', 20),
            'active',
        ));
    }
}
