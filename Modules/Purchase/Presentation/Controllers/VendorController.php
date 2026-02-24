<?php
namespace Modules\Purchase\Presentation\Controllers;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Purchase\Application\UseCases\CreateVendorUseCase;
use Modules\Purchase\Infrastructure\Repositories\VendorRepository;
use Modules\Purchase\Presentation\Requests\StoreVendorRequest;
use Modules\Shared\Application\ResponseFormatter;
class VendorController extends Controller
{
    public function __construct(
        private CreateVendorUseCase $createUseCase,
        private VendorRepository $repo,
    ) {}
    public function index(): JsonResponse
    {
        return ResponseFormatter::paginated($this->repo->paginate(request()->all(), 15));
    }
    public function store(StoreVendorRequest $request): JsonResponse
    {
        $vendor = $this->createUseCase->execute($request->validated());
        return ResponseFormatter::success($vendor, 'Vendor created.', 201);
    }
    public function show(string $id): JsonResponse
    {
        $vendor = $this->repo->findById($id);
        if (!$vendor) return ResponseFormatter::error('Not found.', [], 404);
        return ResponseFormatter::success($vendor);
    }
    public function update(StoreVendorRequest $request, string $id): JsonResponse
    {
        $vendor = $this->repo->update($id, $request->validated());
        return ResponseFormatter::success($vendor, 'Updated.');
    }
    public function destroy(string $id): JsonResponse
    {
        $this->repo->delete($id);
        return ResponseFormatter::success(null, 'Deleted.');
    }
}
