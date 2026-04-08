<?php

declare(strict_types=1);

namespace Modules\Returns\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Returns\Application\Contracts\ReturnLineServiceInterface;
use Modules\Returns\Infrastructure\Http\Resources\ReturnLineResource;

class ReturnLineController extends Controller
{
    public function __construct(
        private readonly ReturnLineServiceInterface $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $returnType = (string) $request->query('return_type', '');
        $returnId = (string) $request->query('return_id', '');
        $lines = $this->service->getLinesForReturn($tenantId, $returnType, $returnId);

        return response()->json(ReturnLineResource::collection($lines));
    }

    public function store(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $entity = $this->service->addReturnLine($tenantId, $request->all());

        return response()->json(new ReturnLineResource($entity), 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $entity = $this->service->getReturnLine($tenantId, $id);

        return response()->json(new ReturnLineResource($entity));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $entity = $this->service->updateReturnLine($tenantId, $id, $request->all());

        return response()->json(new ReturnLineResource($entity));
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $this->service->deleteReturnLine($tenantId, $id);

        return response()->json(null, 204);
    }
}
