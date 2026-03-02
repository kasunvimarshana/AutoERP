<?php

declare(strict_types=1);

namespace Modules\Product\Interfaces\Http\Controllers;

use App\Shared\Abstractions\BaseController;
use DomainException;
use Illuminate\Http\JsonResponse;
use Modules\Product\Application\Commands\SetUomConversionsCommand;
use Modules\Product\Application\Services\UomConversionService;
use Modules\Product\Domain\Entities\UomConversion;
use Modules\Product\Interfaces\Http\Requests\SetUomConversionsRequest;
use Modules\Product\Interfaces\Http\Resources\UomConversionResource;

class UomConversionController extends BaseController
{
    public function __construct(
        private readonly UomConversionService $uomConversionService,
    ) {}

    /**
     * List all UOM conversions for a product.
     */
    public function index(int $productId): JsonResponse
    {
        $tenantId = (int) request('tenant_id');

        if (! $this->uomConversionService->productExists($productId, $tenantId)) {
            return $this->error('Product not found', status: 404);
        }

        $conversions = $this->uomConversionService->listConversions($productId, $tenantId);

        return $this->success(
            data: array_map(
                fn (UomConversion $c) => (new UomConversionResource($c))->resolve(),
                $conversions
            ),
            message: 'UOM conversions retrieved successfully',
        );
    }

    /**
     * Replace all UOM conversions for a product.
     *
     * This is an idempotent replace-all operation. All existing conversions for
     * the product are deleted and replaced with the provided set in a single
     * DB transaction, ensuring consistency.
     */
    public function store(SetUomConversionsRequest $request, int $productId): JsonResponse
    {
        $tenantId = (int) $request->query('tenant_id', '0');

        try {
            $conversions = $this->uomConversionService->setConversions(new SetUomConversionsCommand(
                productId: $productId,
                tenantId: $tenantId,
                conversions: $request->validated('conversions'),
            ));

            return $this->success(
                data: array_map(
                    fn (UomConversion $c) => (new UomConversionResource($c))->resolve(),
                    $conversions
                ),
                message: 'UOM conversions saved successfully',
                status: 201,
            );
        } catch (DomainException $e) {
            return $this->error($e->getMessage(), status: 404);
        }
    }

    /**
     * Convert a quantity between two UOMs for a specific product.
     *
     * Query parameters: tenant_id, quantity, from_uom, to_uom
     */
    public function convert(int $productId): JsonResponse
    {
        $tenantId = (int) request('tenant_id');
        $quantity = (string) request('quantity', '1');
        $fromUom = (string) request('from_uom', '');
        $toUom = (string) request('to_uom', '');

        if (! $this->uomConversionService->productExists($productId, $tenantId)) {
            return $this->error('Product not found', status: 404);
        }

        if ($fromUom === '' || $toUom === '') {
            return $this->error('from_uom and to_uom query parameters are required', status: 422);
        }

        $result = $this->uomConversionService->convertQuantity($productId, $tenantId, $quantity, $fromUom, $toUom);

        if ($result === null) {
            return $this->error(
                "No conversion path found from '{$fromUom}' to '{$toUom}' for this product.",
                status: 422,
            );
        }

        return $this->success(
            data: [
                'product_id' => $productId,
                'from_uom' => $fromUom,
                'to_uom' => $toUom,
                'input_quantity' => $quantity,
                'output_quantity' => $result,
            ],
            message: 'Quantity converted successfully',
        );
    }
}
