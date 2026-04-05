<?php
declare(strict_types=1);
namespace Modules\Pricing\Infrastructure\Http\Controllers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Pricing\Domain\RepositoryInterfaces\TaxRateRepositoryInterface;
use Illuminate\Routing\Controller;
class TaxRateController extends Controller {
    public function __construct(private readonly TaxRateRepositoryInterface $repo) {}
    public function index(Request $request): JsonResponse { return response()->json($this->repo->findByTenant((int)$request->input('tenant_id'))); }
    public function show(int $id): JsonResponse { $e=$this->repo->findById($id); return response()->json($e??['message'=>'Not found'],$e?200:404); }
    public function store(Request $request): JsonResponse { return response()->json($this->repo->create($request->all()),201); }
    public function update(Request $request, int $id): JsonResponse { return response()->json($this->repo->update($id,$request->all())); }
    public function destroy(int $id): JsonResponse { $this->repo->delete($id); return response()->json(null,204); }
}
