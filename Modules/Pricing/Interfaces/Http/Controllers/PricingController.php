<?php

declare(strict_types=1);

namespace Modules\Pricing\Interfaces\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Core\Interfaces\Http\Resources\ApiResponse;
use Modules\Pricing\Application\DTOs\CreateProductPriceDTO;
use Modules\Pricing\Application\DTOs\PriceCalculationDTO;
use Modules\Pricing\Application\Services\PricingService;

/**
 * Pricing controller.
 *
 * Input validation and response formatting ONLY.
 * All business logic is delegated to PricingService.
 *
 * @OA\Tag(name="Pricing", description="Price calculation and price list management")
 */
class PricingController extends Controller
{
    public function __construct(private readonly PricingService $service) {}

    /**
     * @OA\Post(
     *     path="/api/v1/pricing/calculate",
     *     tags={"Pricing"},
     *     summary="Calculate the final price for a product",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"product_id","quantity","date"},
     *             @OA\Property(property="product_id", type="integer"),
     *             @OA\Property(property="quantity", type="string", example="10.0000"),
     *             @OA\Property(property="uom_id", type="integer", nullable=true),
     *             @OA\Property(property="customer_id", type="integer", nullable=true),
     *             @OA\Property(property="location_id", type="integer", nullable=true),
     *             @OA\Property(property="customer_tier", type="string", nullable=true),
     *             @OA\Property(property="date", type="string", format="date")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Calculated price",
     *         @OA\JsonContent(
     *             @OA\Property(property="unit_price", type="string"),
     *             @OA\Property(property="discount", type="string"),
     *             @OA\Property(property="final_price", type="string"),
     *             @OA\Property(property="currency", type="string")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function calculatePrice(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id'    => ['required', 'integer'],
            'quantity'      => ['required', 'numeric', 'min:0'],
            'uom_id'        => ['nullable', 'integer'],
            'customer_id'   => ['nullable', 'integer'],
            'location_id'   => ['nullable', 'integer'],
            'customer_tier' => ['nullable', 'string', 'max:100'],
            'date'          => ['required', 'date'],
        ]);

        $dto    = PriceCalculationDTO::fromArray($validated);
        $result = $this->service->calculatePrice($dto);

        return ApiResponse::success($result, 'Price calculated.');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/pricing/lists",
     *     tags={"Pricing"},
     *     summary="List all price lists",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="List of price lists"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function listPriceLists(): JsonResponse
    {
        $priceLists = $this->service->listPriceLists();

        return ApiResponse::success($priceLists, 'Price lists retrieved.');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/pricing/lists",
     *     tags={"Pricing"},
     *     summary="Create a new price list",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="currency_code", type="string", example="USD"),
     *             @OA\Property(property="is_default", type="boolean"),
     *             @OA\Property(property="is_active", type="boolean")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Price list created"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function createPriceList(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'          => ['required', 'string', 'max:255'],
            'currency_code' => ['nullable', 'string', 'size:3'],
            'is_default'    => ['nullable', 'boolean'],
            'is_active'     => ['nullable', 'boolean'],
        ]);

        $priceList = $this->service->createPriceList($validated);

        return ApiResponse::created($priceList, 'Price list created.');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/pricing/lists/{id}",
     *     tags={"Pricing"},
     *     summary="Show a single price list",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Price list details"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function showPriceList(int $id): JsonResponse
    {
        $priceList = $this->service->showPriceList($id);

        return ApiResponse::success($priceList, 'Price list retrieved.');
    }

    /**
     * @OA\Put(
     *     path="/api/v1/pricing/lists/{id}",
     *     tags={"Pricing"},
     *     summary="Update an existing price list",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="currency_code", type="string", example="USD"),
     *             @OA\Property(property="is_default", type="boolean"),
     *             @OA\Property(property="is_active", type="boolean")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Price list updated"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function updatePriceList(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'name'          => ['sometimes', 'string', 'max:255'],
            'currency_code' => ['nullable', 'string', 'size:3'],
            'is_default'    => ['nullable', 'boolean'],
            'is_active'     => ['nullable', 'boolean'],
        ]);

        $priceList = $this->service->updatePriceList($id, $validated);

        return ApiResponse::success($priceList, 'Price list updated.');
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/pricing/lists/{id}",
     *     tags={"Pricing"},
     *     summary="Delete a price list",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Price list deleted"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function deletePriceList(int $id): JsonResponse
    {
        $this->service->deletePriceList($id);

        return ApiResponse::success(null, 'Price list deleted.');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/pricing/discount-rules",
     *     tags={"Pricing"},
     *     summary="List all discount rules",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="List of discount rules"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function listDiscountRules(): JsonResponse
    {
        $rules = $this->service->listDiscountRules();

        return ApiResponse::success($rules, 'Discount rules retrieved.');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/pricing/discount-rules",
     *     tags={"Pricing"},
     *     summary="Create a new discount rule",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","discount_type","discount_value"},
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="discount_type", type="string", enum={"percentage","flat"}),
     *             @OA\Property(property="discount_value", type="string", example="10.0000"),
     *             @OA\Property(property="apply_to", type="string", enum={"all","product"}),
     *             @OA\Property(property="product_id", type="integer", nullable=true),
     *             @OA\Property(property="customer_tier", type="string", nullable=true),
     *             @OA\Property(property="location_id", type="integer", nullable=true),
     *             @OA\Property(property="min_quantity", type="string", nullable=true),
     *             @OA\Property(property="valid_from", type="string", format="date", nullable=true),
     *             @OA\Property(property="valid_to", type="string", format="date", nullable=true),
     *             @OA\Property(property="is_active", type="boolean")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Discount rule created"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function createDiscountRule(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'          => ['required', 'string', 'max:255'],
            'discount_type' => ['required', 'string', 'in:percentage,flat'],
            'discount_value' => ['required', 'numeric', 'min:0'],
            'apply_to'      => ['nullable', 'string', 'in:all,product'],
            'product_id'    => ['nullable', 'integer'],
            'customer_tier' => ['nullable', 'string', 'max:100'],
            'location_id'   => ['nullable', 'integer'],
            'min_quantity'  => ['nullable', 'numeric', 'min:0'],
            'valid_from'    => ['nullable', 'date'],
            'valid_to'      => ['nullable', 'date'],
            'is_active'     => ['nullable', 'boolean'],
        ]);

        $rule = $this->service->createDiscountRule($validated);

        return ApiResponse::created($rule, 'Discount rule created.');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/pricing/discount-rules/{id}",
     *     tags={"Pricing"},
     *     summary="Show a single discount rule",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Discount rule details"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function showDiscountRule(int $id): JsonResponse
    {
        $rule = $this->service->showDiscountRule($id);

        return ApiResponse::success($rule, 'Discount rule retrieved.');
    }

    /**
     * @OA\Put(
     *     path="/api/v1/pricing/discount-rules/{id}",
     *     tags={"Pricing"},
     *     summary="Update an existing discount rule",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="discount_type", type="string", enum={"percentage","flat"}),
     *             @OA\Property(property="discount_value", type="string", example="10.0000"),
     *             @OA\Property(property="apply_to", type="string", enum={"all","product"}),
     *             @OA\Property(property="product_id", type="integer", nullable=true),
     *             @OA\Property(property="customer_tier", type="string", nullable=true),
     *             @OA\Property(property="location_id", type="integer", nullable=true),
     *             @OA\Property(property="min_quantity", type="string", nullable=true),
     *             @OA\Property(property="valid_from", type="string", format="date", nullable=true),
     *             @OA\Property(property="valid_to", type="string", format="date", nullable=true),
     *             @OA\Property(property="is_active", type="boolean")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Discount rule updated"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function updateDiscountRule(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'name'           => ['sometimes', 'string', 'max:255'],
            'discount_type'  => ['sometimes', 'string', 'in:percentage,flat'],
            'discount_value' => ['sometimes', 'numeric', 'min:0'],
            'apply_to'       => ['nullable', 'string', 'in:all,product'],
            'product_id'     => ['nullable', 'integer'],
            'customer_tier'  => ['nullable', 'string', 'max:100'],
            'location_id'    => ['nullable', 'integer'],
            'min_quantity'   => ['nullable', 'numeric', 'min:0'],
            'valid_from'     => ['nullable', 'date'],
            'valid_to'       => ['nullable', 'date'],
            'is_active'      => ['nullable', 'boolean'],
        ]);

        $rule = $this->service->updateDiscountRule($id, $validated);

        return ApiResponse::success($rule, 'Discount rule updated.');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/products/{productId}/prices",
     *     tags={"Pricing"},
     *     summary="List all product price entries for a product",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="productId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="List of product prices"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function listProductPrices(int $productId): JsonResponse
    {
        $prices = $this->service->listProductPrices($productId);

        return ApiResponse::success($prices, 'Product prices retrieved.');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/products/{productId}/prices",
     *     tags={"Pricing"},
     *     summary="Create a new product price entry",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="productId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"price_list_id","uom_id","selling_price"},
     *             @OA\Property(property="price_list_id", type="integer"),
     *             @OA\Property(property="uom_id", type="integer"),
     *             @OA\Property(property="selling_price", type="string", example="99.9900"),
     *             @OA\Property(property="cost_price", type="string", nullable=true),
     *             @OA\Property(property="min_quantity", type="string", nullable=true),
     *             @OA\Property(property="valid_from", type="string", format="date", nullable=true),
     *             @OA\Property(property="valid_to", type="string", format="date", nullable=true)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Product price created"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function createProductPrice(Request $request, int $productId): JsonResponse
    {
        $validated = $request->validate([
            'price_list_id' => ['required', 'integer'],
            'uom_id'        => ['required', 'integer'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'cost_price'    => ['nullable', 'numeric', 'min:0'],
            'min_quantity'  => ['nullable', 'numeric', 'min:0'],
            'valid_from'    => ['nullable', 'date'],
            'valid_to'      => ['nullable', 'date'],
        ]);

        $validated['product_id'] = $productId;

        $dto   = CreateProductPriceDTO::fromArray($validated);
        $price = $this->service->createProductPrice($dto);

        return ApiResponse::created($price, 'Product price created.');
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/pricing/discount-rules/{id}",
     *     tags={"Pricing"},
     *     summary="Delete a discount rule",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Discount rule deleted"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function deleteDiscountRule(int $id): JsonResponse
    {
        $this->service->deleteDiscountRule($id);

        return ApiResponse::success(null, 'Discount rule deleted.');
    }
}
