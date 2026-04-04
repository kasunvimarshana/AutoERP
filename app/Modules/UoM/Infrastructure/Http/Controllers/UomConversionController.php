<?php
namespace Modules\UoM\Infrastructure\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\UoM\Application\Contracts\CreateUomConversionServiceInterface;
use Modules\UoM\Application\Contracts\DeleteUomConversionServiceInterface;
use Modules\UoM\Application\Contracts\UpdateUomConversionServiceInterface;
use Modules\UoM\Application\DTOs\UomConversionData;
use Modules\UoM\Domain\RepositoryInterfaces\UomConversionRepositoryInterface;
use Modules\UoM\Infrastructure\Http\Resources\UomConversionResource;

class UomConversionController extends Controller
{
    public function __construct(
        private readonly UomConversionRepositoryInterface $repository,
        private readonly CreateUomConversionServiceInterface $createService,
        private readonly UpdateUomConversionServiceInterface $updateService,
        private readonly DeleteUomConversionServiceInterface $deleteService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $fromId    = $request->query('from_uom_id');
        $toId      = $request->query('to_uom_id');
        $productId = $request->query('product_id');

        if ($fromId && $toId) {
            $conversion = $this->repository->findByFromTo(
                (int) $fromId,
                (int) $toId,
                $productId !== null ? (int) $productId : null
            );
            return response()->json($conversion ? [new UomConversionResource($conversion)] : []);
        }

        return response()->json([]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from_uom_id' => 'required|integer',
            'to_uom_id'   => 'required|integer',
            'factor'      => 'required|numeric',
            'product_id'  => 'nullable|integer',
        ]);

        $data = new UomConversionData(
            fromUomId: $validated['from_uom_id'],
            toUomId: $validated['to_uom_id'],
            factor: $validated['factor'],
            productId: $validated['product_id'] ?? null,
        );

        $conversion = $this->createService->execute($data);
        return response()->json(new UomConversionResource($conversion), 201);
    }

    public function show(int $id): JsonResponse
    {
        $conversion = $this->repository->findById($id);
        if (!$conversion) {
            return response()->json(['message' => 'Not found'], 404);
        }
        return response()->json(new UomConversionResource($conversion));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'from_uom_id' => 'required|integer',
            'to_uom_id'   => 'required|integer',
            'factor'      => 'required|numeric',
            'product_id'  => 'nullable|integer',
        ]);

        $data = new UomConversionData(
            fromUomId: $validated['from_uom_id'],
            toUomId: $validated['to_uom_id'],
            factor: $validated['factor'],
            productId: $validated['product_id'] ?? null,
        );

        $conversion = $this->updateService->execute($id, $data);
        return response()->json(new UomConversionResource($conversion));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->deleteService->execute($id);
        return response()->json(null, 204);
    }
}
