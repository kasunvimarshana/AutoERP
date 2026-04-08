<?php
declare(strict_types=1);
namespace Modules\Accounting\Infrastructure\Http\Controllers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Accounting\Application\Contracts\JournalEntryServiceInterface;
use Modules\Accounting\Infrastructure\Http\Resources\JournalEntryResource;
class JournalEntryController extends Controller
{
    public function __construct(
        private readonly JournalEntryServiceInterface $journalEntryService,
    ) {}
    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $entries  = $this->journalEntryService->getAllEntries($tenantId, $request->query());
        return response()->json(JournalEntryResource::collection($entries));
    }
    public function store(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $entry = $this->journalEntryService->createEntry(
            $tenantId,
            $request->except('lines'),
            $request->input('lines', [])
        );
        return response()->json(new JournalEntryResource($entry), 201);
    }
    public function show(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $entry    = $this->journalEntryService->getEntry($tenantId, $id);
        return response()->json(new JournalEntryResource($entry));
    }
    public function post(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $entry    = $this->journalEntryService->postEntry($tenantId, $id);
        return response()->json(new JournalEntryResource($entry));
    }
    public function void(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $entry    = $this->journalEntryService->voidEntry($tenantId, $id);
        return response()->json(new JournalEntryResource($entry));
    }
}
