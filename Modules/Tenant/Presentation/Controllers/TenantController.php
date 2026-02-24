<?php
namespace Modules\Tenant\Presentation\Controllers;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Shared\Application\ResponseFormatter;
use Modules\Tenant\Application\UseCases\CreateTenantUseCase;
use Modules\Tenant\Application\UseCases\SuspendTenantUseCase;
use Modules\Tenant\Infrastructure\Repositories\TenantRepository;
use Modules\Tenant\Presentation\Requests\StoreTenantRequest;
use Modules\Tenant\Presentation\Requests\UpdateTenantRequest;
class TenantController extends Controller
{
    public function __construct(
        private CreateTenantUseCase $createUseCase,
        private SuspendTenantUseCase $suspendUseCase,
        private TenantRepository $repo,
    ) {}
    public function index(): JsonResponse
    {
        $tenants = $this->repo->paginate([], 15);
        return ResponseFormatter::paginated($tenants);
    }
    public function store(StoreTenantRequest $request): JsonResponse
    {
        $tenant = $this->createUseCase->execute($request->validated());
        return ResponseFormatter::success($tenant, 'Tenant created.', 201);
    }
    public function show(string $id): JsonResponse
    {
        $tenant = $this->repo->findById($id);
        if (! $tenant) return ResponseFormatter::error('Not found.', [], 404);
        return ResponseFormatter::success($tenant);
    }
    public function update(UpdateTenantRequest $request, string $id): JsonResponse
    {
        $tenant = $this->repo->update($id, $request->validated());
        return ResponseFormatter::success($tenant, 'Updated.');
    }
    public function suspend(string $id): JsonResponse
    {
        $this->suspendUseCase->execute(['tenant_id' => $id]);
        return ResponseFormatter::success(null, 'Tenant suspended.');
    }
}
