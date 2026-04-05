<?php
declare(strict_types=1);
namespace Modules\Accounting\Infrastructure\Http\Controllers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Accounting\Application\Contracts\PostJournalEntryServiceInterface;
use Modules\Accounting\Domain\RepositoryInterfaces\JournalEntryRepositoryInterface;
use Illuminate\Routing\Controller;
class JournalEntryController extends Controller {
    public function __construct(
        private readonly JournalEntryRepositoryInterface $repo,
        private readonly PostJournalEntryServiceInterface $postService,
    ) {}
    public function index(Request $r): JsonResponse { return response()->json($this->repo->findByTenant((int)$r->input('tenant_id'),$r->only('status'))); }
    public function show(int $id): JsonResponse { $je=$this->repo->findById($id); return response()->json($je??['message'=>'Not found'],$je?200:404); }
    public function store(Request $r): JsonResponse { return response()->json($this->repo->create(array_merge($r->except('lines'),['status'=>'draft']),$r->input('lines',[])),201); }
    public function post(int $id): JsonResponse { return response()->json($this->postService->execute($id)); }
    public function destroy(int $id): JsonResponse { $this->repo->delete($id); return response()->json(null,204); }
}
