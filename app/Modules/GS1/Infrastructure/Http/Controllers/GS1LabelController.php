<?php
declare(strict_types=1);
namespace Modules\GS1\Infrastructure\Http\Controllers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\GS1\Domain\RepositoryInterfaces\Gs1LabelRepositoryInterface;
class Gs1LabelController extends Controller {
    public function __construct(private readonly Gs1LabelRepositoryInterface $repo) {}
    public function index(Request $request): JsonResponse {
        return response()->json($this->repo->findByProduct((int)$request->input('tenant_id'),(int)$request->input('product_id')));
    }
    public function show(int $id): JsonResponse { $l=$this->repo->findById($id); return response()->json($l??['message'=>'Not found'],($l?200:404)); }
    public function store(Request $request): JsonResponse { return response()->json($this->repo->create($request->all()),201); }
    public function update(Request $request, int $id): JsonResponse { return response()->json($this->repo->update($id,$request->all())); }
    public function destroy(int $id): JsonResponse { $this->repo->delete($id); return response()->json(null,204); }
}
