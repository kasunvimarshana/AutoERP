<?php

declare(strict_types=1);

namespace Modules\Payment\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Payment\Http\Requests\ListPaymentMethodRequest;
use Modules\Payment\Http\Requests\UpsertPaymentMethodRequest;
use Modules\Payment\Http\Resources\PaymentMethodResource;
use Modules\Payment\Services\PaymentAuthorizationService;
use Modules\Payment\Services\PaymentMethodService;

final class PaymentMethodController
{
    public function __construct(
        private readonly PaymentAuthorizationService $authorization,
        private readonly PaymentMethodService $methods,
    ) {}

    public function index(ListPaymentMethodRequest $request): AnonymousResourceCollection
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PaymentAuthorizationService::METHODS_VIEW);

        return PaymentMethodResource::collection($this->methods->paginate([
            'effective' => ! $request->boolean('include_overrides', false),
            'active_only' => ! $request->has('is_active') || $request->boolean('is_active'),
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : null,
            'direction' => $request->filled('direction') ? (string) $request->input('direction') : null,
            'method_type' => $request->filled('method_type') ? (string) $request->input('method_type') : null,
            'search' => $request->filled('search') ? (string) $request->input('search') : null,
        ], $request->tenantId(), $request->organizationUnitId(), $request->perPage()));
    }

    public function store(UpsertPaymentMethodRequest $request): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PaymentAuthorizationService::METHODS_CREATE);

        return (new PaymentMethodResource($this->methods->create(
            $request->validated(),
            $request->tenantId(),
            $request->organizationUnitId(),
        )))->response()->setStatusCode(201);
    }

    public function show(ListPaymentMethodRequest $request, int $id): PaymentMethodResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PaymentAuthorizationService::METHODS_VIEW);

        return new PaymentMethodResource($this->methods->find($id, $request->tenantId(), $request->organizationUnitId()));
    }

    public function update(UpsertPaymentMethodRequest $request, int $id): PaymentMethodResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PaymentAuthorizationService::METHODS_UPDATE);
        $method = $this->methods->find($id, $request->tenantId(), $request->organizationUnitId());

        return new PaymentMethodResource($this->methods->update($method, $request->validated()));
    }

    public function activate(ListPaymentMethodRequest $request, int $id): PaymentMethodResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PaymentAuthorizationService::METHODS_UPDATE);

        return new PaymentMethodResource($this->methods->setActive(
            $this->methods->find($id, $request->tenantId(), $request->organizationUnitId()),
            true,
        ));
    }

    public function deactivate(ListPaymentMethodRequest $request, int $id): PaymentMethodResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PaymentAuthorizationService::METHODS_UPDATE);

        return new PaymentMethodResource($this->methods->setActive(
            $this->methods->find($id, $request->tenantId(), $request->organizationUnitId()),
            false,
        ));
    }

    public function destroy(ListPaymentMethodRequest $request, int $id): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PaymentAuthorizationService::METHODS_DELETE);
        $this->methods->delete($this->methods->find($id, $request->tenantId(), $request->organizationUnitId()));

        return response()->json(status: 204);
    }
}
