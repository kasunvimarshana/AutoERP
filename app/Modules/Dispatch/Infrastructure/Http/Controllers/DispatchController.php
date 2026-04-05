<?php
declare(strict_types=1);
namespace Modules\Dispatch\Infrastructure\Http\Controllers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Dispatch\Domain\RepositoryInterfaces\DispatchRepositoryInterface;
class DispatchController extends Controller {
    public function __construct(private readonly DispatchRepositoryInterface $repo) {}
    public function index(Request $r): JsonResponse { return response()->json($this->repo->findByTenant((int)$r->input('tenant_id'))); }
    public function show(int $id): JsonResponse { $d=$this->repo->findById($id); return response()->json($d??['message'=>'Not found'],$d?200:404); }
    public function store(Request $r): JsonResponse { return response()->json($this->repo->create(array_merge($r->except('lines'),['status'=>'pending']),$r->input('lines',[])),201); }
    public function ship(Request $r, int $id): JsonResponse {
        $d=$this->repo->findById($id);
        if(!$d) return response()->json(['message'=>'Not found'],404);
        $d->ship($r->input('carrier',''),$r->input('tracking_number',''));
        $this->repo->update($id,['status'=>'shipped','carrier'=>$r->input('carrier'),'tracking_number'=>$r->input('tracking_number'),'shipped_at'=>now()]);
        return response()->json($this->repo->findById($id));
    }
    public function destroy(int $id): JsonResponse { $this->repo->delete($id); return response()->json(null,204); }
}
