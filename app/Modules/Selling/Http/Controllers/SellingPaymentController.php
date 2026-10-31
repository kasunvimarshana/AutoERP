<?php

declare(strict_types=1);

namespace Modules\Selling\Http\Controllers;

use Modules\Payment\Http\Requests\StorePaymentRequest;
use Modules\Payment\Http\Resources\PaymentResource;
use Modules\Selling\Services\SellingPaymentFinalizationService;

final class SellingPaymentController
{
    public function store(StorePaymentRequest $request, SellingPaymentFinalizationService $service): PaymentResource
    {
        return new PaymentResource($service->finalize($request->toData()));
    }
}
