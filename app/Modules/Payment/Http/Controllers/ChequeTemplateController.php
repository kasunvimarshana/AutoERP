<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Payment\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Payment\Constants\PaymentPermission;
use Modules\Payment\Http\Requests\ListChequeTemplateRequest;
use Modules\Payment\Http\Requests\UpsertChequeTemplateRequest;
use Modules\Payment\Http\Resources\ChequeTemplateResource;
use Modules\Payment\Services\ChequeTemplateService;
use Modules\Payment\Services\PaymentAuthorizationService;

final class ChequeTemplateController
{
    public function __construct(private readonly PaymentAuthorizationService $authorization) {}

    public function index(
        ListChequeTemplateRequest $request,
        ChequeTemplateService $service,
    ): AnonymousResourceCollection {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PaymentPermission::TEMPLATES_VIEW);

        $isActive = $request->filled('is_active') ? $request->boolean('is_active') : null;

        if ($request->boolean('all')) {
            return ChequeTemplateResource::collection($service->list(
                $request->tenantId(),
                $request->organizationUnitId(),
                $isActive,
            ));
        }

        return ChequeTemplateResource::collection($service->paginate(
            $request->tenantId(),
            $request->organizationUnitId(),
            $isActive,
            $request->filled('search') ? (string) $request->input('search') : null,
            $request->perPage(),
        ));
    }

    public function store(
        UpsertChequeTemplateRequest $request,
        ChequeTemplateService $service,
    ): JsonResponse {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PaymentPermission::TEMPLATES_CREATE);

        return (new ChequeTemplateResource($service->create(
            $request->validated(),
            $request->tenantId(),
            $request->organizationUnitId(),
        )))->response()->setStatusCode(201);
    }

    public function show(
        ListChequeTemplateRequest $request,
        int $id,
        ChequeTemplateService $service,
    ): ChequeTemplateResource {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PaymentPermission::TEMPLATES_VIEW);

        return new ChequeTemplateResource($service->find(
            $id,
            $request->tenantId(),
            $request->organizationUnitId(),
        ));
    }

    public function update(
        UpsertChequeTemplateRequest $request,
        int $id,
        ChequeTemplateService $service,
    ): ChequeTemplateResource {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PaymentPermission::TEMPLATES_UPDATE);
        $expectedVersion = $request->filled('expected_version') ? (int) $request->input('expected_version') : null;

        $template = $service->find($id, $request->tenantId(), $request->organizationUnitId());
        $saved = $service->update($template, $request->validated(), $expectedVersion);

        return new ChequeTemplateResource($saved);
    }

    public function destroy(
        ListChequeTemplateRequest $request,
        int $id,
        ChequeTemplateService $service,
    ): JsonResponse {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PaymentPermission::TEMPLATES_DELETE);
        $service->delete($service->find($id, $request->tenantId(), $request->organizationUnitId()));

        return response()->json(status: 204);
    }
}
