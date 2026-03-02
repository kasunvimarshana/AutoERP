<?php

declare(strict_types=1);

namespace Modules\Tenant\Interfaces\Http\Controllers;

use App\Shared\Abstractions\BaseController;
use Illuminate\Http\JsonResponse;
use Modules\Tenant\Application\Commands\CreateTenantCommand;
use Modules\Tenant\Application\Services\TenantService;
use Modules\Tenant\Interfaces\Http\Requests\CreateTenantRequest;
use Modules\Tenant\Interfaces\Http\Resources\TenantResource;

class TenantController extends BaseController
{
    public function __construct(
        private readonly TenantService $tenantService,
    ) {}

    public function index(): JsonResponse
    {
        $page = (int) request('page', 1);
        $perPage = (int) request('per_page', 25);

        $result = $this->tenantService->listTenants($page, $perPage);

        return $this->success(
            data: array_map(
                fn ($tenant) => (new TenantResource($tenant))->resolve(),
                $result['items']
            ),
            message: 'Tenants retrieved successfully',
            meta: [
                'current_page' => $result['current_page'],
                'last_page' => $result['last_page'],
                'per_page' => $result['per_page'],
                'total' => $result['total'],
            ],
        );
    }

    public function store(CreateTenantRequest $request): JsonResponse
    {
        $tenant = $this->tenantService->createTenant(new CreateTenantCommand(
            name: $request->validated('name'),
            slug: $request->validated('slug'),
            planCode: $request->validated('plan_code', 'free'),
            domain: $request->validated('domain'),
            currency: $request->validated('currency'),
        ));

        return $this->success(
            data: (new TenantResource($tenant))->resolve(),
            message: 'Tenant created successfully',
            status: 201,
        );
    }

    public function show(int $id): JsonResponse
    {
        $tenant = $this->tenantService->findTenantById($id);

        if ($tenant === null) {
            return $this->error('Tenant not found', status: 404);
        }

        return $this->success(
            data: (new TenantResource($tenant))->resolve(),
            message: 'Tenant retrieved successfully',
        );
    }
}
