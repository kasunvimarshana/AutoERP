<?php
namespace Modules\Inventory\Presentation\Controllers;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Inventory\Infrastructure\Repositories\StockLevelRepository;
use Modules\Shared\Application\ResponseFormatter;
class StockLevelController extends Controller
{
    public function __construct(private StockLevelRepository $repo) {}
    public function index(): JsonResponse
    {
        return ResponseFormatter::paginated($this->repo->paginate(request()->all(), 15));
    }
}
