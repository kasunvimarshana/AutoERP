<?php

declare(strict_types=1);

namespace Modules\Payment\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Payment\Http\Requests\ListChequeTemplateRequest;
use Modules\Payment\Http\Requests\UpsertChequeTemplateRequest;
use Modules\Payment\Http\Resources\ChequeTemplateResource;
use Modules\Payment\Services\ChequeTemplateService;

final class ChequeTemplateController
{
    public function index(
        ListChequeTemplateRequest $request,
        ChequeTemplateService $service,
    ): AnonymousResourceCollection {
        return ChequeTemplateResource::collection($service->list(
            $request->tenantId(),
            $request->organizationUnitId(),
            $request->filled('is_active') ? $request->boolean('is_active') : null,
        ));
    }

    public function store(
        UpsertChequeTemplateRequest $request,
        ChequeTemplateService $service,
    ): JsonResponse {
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
        $template = $service->find($id, $request->tenantId(), $request->organizationUnitId());

        return new ChequeTemplateResource($service->update($template, $request->validated()));
    }

    public function destroy(
        ListChequeTemplateRequest $request,
        int $id,
        ChequeTemplateService $service,
    ): JsonResponse {
        $service->delete($service->find($id, $request->tenantId(), $request->organizationUnitId()));

        return response()->json(status: 204);
    }
}
