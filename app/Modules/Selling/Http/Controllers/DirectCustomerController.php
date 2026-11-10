<?php

declare(strict_types=1);

namespace Modules\Selling\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Customer\Http\Resources\CustomerSummaryResource;
use Modules\Customer\Services\DirectCustomerService;
use Modules\Selling\Http\Requests\DirectCustomerRequest;

final class DirectCustomerController
{
    public function store(DirectCustomerRequest $request, DirectCustomerService $customers): JsonResponse
    {
        return (new CustomerSummaryResource($customers->ensure($request->tenantId())))
            ->response()
            ->setStatusCode(200);
    }
}
