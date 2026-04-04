<?php
namespace Modules\GS1\Infrastructure\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\GS1\Application\Contracts\CreateGS1BarcodeServiceInterface;
use Modules\GS1\Application\DTOs\GS1BarcodeData;
use Modules\GS1\Domain\RepositoryInterfaces\GS1BarcodeRepositoryInterface;
use Modules\GS1\Infrastructure\Http\Resources\GS1BarcodeResource;

class GS1BarcodeController extends Controller
{
    public function __construct(
        private readonly GS1BarcodeRepositoryInterface $repository,
        private readonly CreateGS1BarcodeServiceInterface $createService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = (int) $request->query('tenant_id', 0);
        $barcodes = $this->repository->findAll(
            $tenantId,
            $request->only(['barcode_type', 'product_id', 'is_active']),
        );
        return response()->json($barcodes);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id'          => 'required|integer',
            'product_id'         => 'required|integer',
            'gs1_company_prefix' => 'required|string|max:20',
            'item_reference'     => 'required|string|max:20',
            'barcode_type'       => 'required|string|in:GTIN-8,GTIN-12,GTIN-13,GTIN-14,SSCC,GS1-128',
            'variant_id'         => 'nullable|integer',
        ]);

        try {
            $dto = new GS1BarcodeData(
                tenantId:         $validated['tenant_id'],
                productId:        $validated['product_id'],
                gs1CompanyPrefix: $validated['gs1_company_prefix'],
                itemReference:    $validated['item_reference'],
                barcodeType:      $validated['barcode_type'],
                variantId:        $validated['variant_id'] ?? null,
            );
            $barcode = $this->createService->execute($dto);
            return response()->json(new GS1BarcodeResource($barcode), 201);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function show(int $id): JsonResponse
    {
        $barcode = $this->repository->findById($id);
        if (!$barcode) {
            return response()->json(['message' => 'Not found'], 404);
        }
        return response()->json(new GS1BarcodeResource($barcode));
    }
}
