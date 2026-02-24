<?php
namespace Modules\Purchase\Presentation\Controllers;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Purchase\Infrastructure\Repositories\GoodsReceiptRepository;
use Modules\Shared\Application\ResponseFormatter;
class GoodsReceiptController extends Controller
{
    public function __construct(private GoodsReceiptRepository $repo) {}
    public function index(): JsonResponse
    {
        return ResponseFormatter::paginated($this->repo->paginate(request()->all(), 15));
    }
    public function show(string $id): JsonResponse
    {
        $grn = $this->repo->findById($id);
        if (!$grn) return ResponseFormatter::error('Not found.', [], 404);
        return ResponseFormatter::success($grn);
    }
    public function store(): JsonResponse
    {
        return ResponseFormatter::error('Use POST /purchase-orders/{id}/receive to create a receipt.', [], 422);
    }
}
