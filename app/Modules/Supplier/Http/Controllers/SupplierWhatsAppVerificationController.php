<?php

declare(strict_types=1);

namespace Modules\Supplier\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Core\Http\Requests\ConfirmWhatsAppVerificationRequest;
use Modules\Core\Http\Requests\StartWhatsAppVerificationRequest;
use Modules\Supplier\Http\Requests\ListSupplierRequest;
use Modules\Supplier\Services\SupplierAuthorizationService;
use Modules\Supplier\Services\SupplierQueryService;
use Modules\Supplier\Services\SupplierWhatsAppVerificationService;

final class SupplierWhatsAppVerificationController
{
    public function __construct(
        private readonly SupplierQueryService $queries,
        private readonly SupplierAuthorizationService $authorization,
        private readonly SupplierWhatsAppVerificationService $verifications,
    ) {}

    public function show(ListSupplierRequest $request, int $supplier): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), SupplierAuthorizationService::VIEW);

        return response()->json(['data' => $this->verifications->state(
            $this->queries->supplier($supplier, $request->tenantId(), $request->organizationUnitId()),
        )]);
    }

    public function start(StartWhatsAppVerificationRequest $request, int $supplier): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), SupplierAuthorizationService::UPDATE);

        return response()->json(['data' => $this->verifications->start(
            $this->queries->supplier($supplier, $request->tenantId(), $request->organizationUnitId()),
            (int) $request->currentUserId(),
            $request->idempotencyKey(),
        )], 201);
    }

    public function confirm(ConfirmWhatsAppVerificationRequest $request, int $supplier): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), SupplierAuthorizationService::UPDATE);

        return response()->json(['data' => $this->verifications->confirm(
            $this->queries->supplier($supplier, $request->tenantId(), $request->organizationUnitId()),
            (int) $request->currentUserId(),
            $request->code(),
        )]);
    }
}
