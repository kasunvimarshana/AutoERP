<?php
namespace Modules\Audit\Presentation\Controllers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Audit\Domain\Contracts\AuditRepositoryInterface;
use Modules\Shared\Application\ResponseFormatter;
class AuditController extends Controller
{
    public function __construct(private AuditRepositoryInterface $repo) {}

    public function index(Request $request): JsonResponse
    {
        $filters = array_filter([
            'tenant_id'  => app('current.tenant.id') ?: null,
            'model_type' => $request->get('model_type'),
            'model_id'   => $request->get('model_id'),
            'action'     => $request->get('action'),
            'user_id'    => $request->get('user_id'),
        ]);

        return ResponseFormatter::paginated($this->repo->paginate($filters));
    }

    public function show(string $id): JsonResponse
    {
        $log = $this->repo->findById($id);
        if (! $log) return ResponseFormatter::error('Not found.', [], 404);
        return ResponseFormatter::success($log);
    }
}
