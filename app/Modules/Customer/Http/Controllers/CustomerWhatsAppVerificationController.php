<?php

declare(strict_types=1);

namespace Modules\Customer\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Core\Http\Requests\ConfirmWhatsAppVerificationRequest;
use Modules\Core\Http\Requests\StartWhatsAppVerificationRequest;
use Modules\Customer\Http\Requests\ListCustomerRequest;
use Modules\Customer\Services\CustomerAuthorizationService;
use Modules\Customer\Services\CustomerQueryService;
use Modules\Customer\Services\CustomerWhatsAppVerificationService;

final class CustomerWhatsAppVerificationController
{
    public function __construct(
        private readonly CustomerQueryService $queries,
        private readonly CustomerAuthorizationService $authorization,
        private readonly CustomerWhatsAppVerificationService $verifications,
    ) {}

    public function show(ListCustomerRequest $request, int $customer): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), CustomerAuthorizationService::VIEW);

        return response()->json(['data' => $this->verifications->state(
            $this->queries->customer($customer, $request->tenantId(), $request->organizationUnitId()),
        )]);
    }

    public function start(StartWhatsAppVerificationRequest $request, int $customer): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), CustomerAuthorizationService::UPDATE);

        return response()->json(['data' => $this->verifications->start(
            $this->queries->customer($customer, $request->tenantId(), $request->organizationUnitId()),
            (int) $request->currentUserId(),
            $request->idempotencyKey(),
        )], 201);
    }

    public function confirm(ConfirmWhatsAppVerificationRequest $request, int $customer): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), CustomerAuthorizationService::UPDATE);

        return response()->json(['data' => $this->verifications->confirm(
            $this->queries->customer($customer, $request->tenantId(), $request->organizationUnitId()),
            (int) $request->currentUserId(),
            $request->code(),
        )]);
    }
}
