<?php
declare(strict_types=1);
namespace Modules\Accounting\Infrastructure\Http\Controllers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Accounting\Domain\RepositoryInterfaces\AccountRepositoryInterface;
use Illuminate\Routing\Controller;
class AccountController extends Controller {
    public function __construct(private readonly AccountRepositoryInterface $repo) {}
    public function index(Request $r): JsonResponse { return response()->json($this->repo->findByTenant((int)$r->input('tenant_id'))); }
    public function show(int $id): JsonResponse { $a=$this->repo->findById($id); return response()->json($a??['message'=>'Not found'],$a?200:404); }
    public function store(Request $r): JsonResponse { return response()->json($this->repo->create($r->all()),201); }
    public function update(Request $r, int $id): JsonResponse { return response()->json($this->repo->update($id,$r->all())); }
    public function destroy(int $id): JsonResponse { $this->repo->delete($id); return response()->json(null,204); }
}
