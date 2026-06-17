<?php

declare(strict_types=1);

namespace Modules\Purchase\Http\Controllers;

use Modules\Purchase\Http\Requests\FastPurchaseRequest;
use Modules\Purchase\Http\Resources\FastPurchaseResource;
use Modules\Purchase\Services\PurchaseAuthorizationService;
use Modules\Purchase\Services\FastPurchaseService;

final class FastPurchaseController
{
    public function __construct(private readonly PurchaseAuthorizationService $authorization) {}

    public function context(FastPurchaseRequest $request, FastPurchaseService $service): FastPurchaseResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PurchaseAuthorizationService::FAST_PURCHASE_VIEW);

        return new FastPurchaseResource($service->context($request->payload()));
    }

    public function preview(FastPurchaseRequest $request, FastPurchaseService $service): FastPurchaseResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PurchaseAuthorizationService::FAST_PURCHASE_VIEW);

        return new FastPurchaseResource($service->preview($request->payload()));
    }

    public function store(FastPurchaseRequest $request, FastPurchaseService $service): FastPurchaseResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PurchaseAuthorizationService::FAST_PURCHASE_EXECUTE);

        return new FastPurchaseResource($service->create($request->payload()));
    }
}
