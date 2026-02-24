<?php
namespace Modules\Sales\Presentation\Controllers;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Sales\Application\UseCases\CreateCustomerUseCase;
use Modules\Sales\Infrastructure\Repositories\CustomerRepository;
use Modules\Sales\Presentation\Requests\StoreCustomerRequest;
use Modules\Shared\Application\ResponseFormatter;
class CustomerController extends Controller
{
    public function __construct(
        private CreateCustomerUseCase $createUseCase,
        private CustomerRepository $repo,
    ) {}
    public function index(): JsonResponse
    {
        return ResponseFormatter::paginated($this->repo->paginate(request()->all(), 15));
    }
    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $customer = $this->createUseCase->execute($request->validated());
        return ResponseFormatter::success($customer, 'Customer created.', 201);
    }
    public function show(string $id): JsonResponse
    {
        $customer = $this->repo->findById($id);
        if (!$customer) return ResponseFormatter::error('Not found.', [], 404);
        return ResponseFormatter::success($customer);
    }
    public function update(StoreCustomerRequest $request, string $id): JsonResponse
    {
        $customer = $this->repo->update($id, $request->validated());
        return ResponseFormatter::success($customer, 'Updated.');
    }
    public function destroy(string $id): JsonResponse
    {
        $this->repo->delete($id);
        return ResponseFormatter::success(null, 'Deleted.');
    }
}
