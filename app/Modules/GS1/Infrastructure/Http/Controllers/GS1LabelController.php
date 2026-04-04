<?php
namespace Modules\GS1\Infrastructure\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\GS1\Application\Contracts\GenerateGS1LabelServiceInterface;
use Modules\GS1\Application\DTOs\GS1LabelData;
use Modules\GS1\Domain\RepositoryInterfaces\GS1LabelRepositoryInterface;
use Modules\GS1\Infrastructure\Http\Resources\GS1LabelResource;

class GS1LabelController extends Controller
{
    public function __construct(
        private readonly GS1LabelRepositoryInterface $repository,
        private readonly GenerateGS1LabelServiceInterface $generateService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $barcodeId = (int) $request->query('barcode_id', 0);
        $labels    = $this->repository->findByBarcode($barcodeId);
        return response()->json($labels);
    }

    public function show(int $id): JsonResponse
    {
        $label = $this->repository->findById($id);
        if (!$label) {
            return response()->json(['message' => 'Not found'], 404);
        }
        return response()->json(new GS1LabelResource($label));
    }

    public function generateLabel(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id'     => 'required|integer',
            'barcode_id'    => 'required|integer',
            'label_format'  => 'required|string|max:50',
            'batch_id'      => 'nullable|integer',
            'serial_number' => 'nullable|string|max:100',
        ]);

        try {
            $dto = new GS1LabelData(
                tenantId:     $validated['tenant_id'],
                barcodeId:    $validated['barcode_id'],
                labelFormat:  $validated['label_format'],
                batchId:      $validated['batch_id'] ?? null,
                serialNumber: $validated['serial_number'] ?? null,
            );
            $label = $this->generateService->execute($dto);
            return response()->json(new GS1LabelResource($label), 201);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
