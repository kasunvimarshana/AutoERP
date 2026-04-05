<?php
declare(strict_types=1);
namespace Modules\Tenant\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Tenant\Application\Contracts\CreateTenantServiceInterface;
use Modules\Tenant\Application\Contracts\DeleteTenantServiceInterface;
use Modules\Tenant\Application\Contracts\GetTenantServiceInterface;
use Modules\Tenant\Application\Contracts\UpdateTenantServiceInterface;
use Modules\Tenant\Application\DTOs\CreateTenantData;
use Modules\Tenant\Application\DTOs\UpdateTenantData;
use Modules\Tenant\Infrastructure\Http\Resources\TenantResource;

class TenantController extends Controller
{
    public function __construct(
        private readonly GetTenantServiceInterface $getService,
        private readonly CreateTenantServiceInterface $createService,
        private readonly UpdateTenantServiceInterface $updateService,
        private readonly DeleteTenantServiceInterface $deleteService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $result = $this->getService->findAll(
            (int) $request->get('per_page', 15),
            (int) $request->get('page', 1)
        );
        return response()->json($result);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(new TenantResource($this->getService->findById($id)));
    }

    public function store(Request $request): JsonResponse
    {
        $data = CreateTenantData::fromArray($request->all());
        $tenant = $this->createService->execute($data);
        return response()->json(new TenantResource($tenant), 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $data = UpdateTenantData::fromArray($request->all());
        $tenant = $this->updateService->execute($id, $data);
        return response()->json(new TenantResource($tenant));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->deleteService->execute($id);
        return response()->json(null, 204);
    }
}
