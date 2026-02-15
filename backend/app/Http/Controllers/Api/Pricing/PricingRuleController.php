<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Pricing;

use App\Http\Controllers\BaseController;
use App\Models\PricingRule;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * Pricing Rule Controller
 * 
 * Manages dynamic pricing rules for products, categories, and customers
 */
class PricingRuleController extends BaseController
{
    /**
     * Display a listing of pricing rules
     */
    public function index(Request $request): JsonResponse
    {
        $query = PricingRule::query()->with(['product', 'category', 'customer', 'discountTiers']);

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Filter by rule type
        if ($request->filled('rule_type')) {
            $query->where('rule_type', $request->input('rule_type'));
        }

        // Filter by product
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->input('product_id'));
        }

        // Filter by currently valid rules
        if ($request->boolean('currently_valid')) {
            $query->currentlyValid();
        }

        // Ordering
        $query->byPriority($request->input('order', 'desc'));

        $pricingRules = $query->paginate($request->input('per_page', 15));

        return $this->successResponse($pricingRules, 'Pricing rules retrieved successfully');
    }

    /**
     * Store a newly created pricing rule
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:pricing_rules,code,NULL,id,tenant_id,' . auth()->user()->tenant_id,
            'description' => 'nullable|string',
            'rule_type' => 'required|in:product,category,customer,customer_group,seasonal,promotional',
            'is_active' => 'boolean',
            'product_id' => 'nullable|exists:products,id',
            'category_id' => 'nullable|exists:product_categories,id',
            'customer_id' => 'nullable|exists:customers,id',
            'customer_group' => 'nullable|string|max:255',
            'priority' => 'integer|min:0',
            'valid_from' => 'nullable|date',
            'valid_to' => 'nullable|date|after_or_equal:valid_from',
            'time_from' => 'nullable|date_format:H:i:s',
            'time_to' => 'nullable|date_format:H:i:s',
            'days_of_week' => 'nullable|array',
            'days_of_week.*' => 'integer|min:0|max:6',
            'min_quantity' => 'nullable|numeric|min:0',
            'max_quantity' => 'nullable|numeric|min:0',
            'pricing_method' => 'required|in:fixed,markup,markdown,discount',
            'adjustment_type' => 'required|in:flat,percentage',
            'adjustment_value' => 'required|numeric|min:0',
            'fixed_price' => 'nullable|numeric|min:0',
            'can_compound' => 'boolean',
            'exclude_rules' => 'nullable|array',
            'conditions' => 'nullable|array',
        ]);

        DB::beginTransaction();
        try {
            $validated['tenant_id'] = auth()->user()->tenant_id;
            $validated['created_by'] = auth()->id();

            $pricingRule = PricingRule::create($validated);

            DB::commit();

            return $this->successResponse(
                $pricingRule->load(['product', 'category', 'customer']),
                'Pricing rule created successfully',
                201
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to create pricing rule: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Display the specified pricing rule
     */
    public function show(int $id): JsonResponse
    {
        $pricingRule = PricingRule::with(['product', 'category', 'customer', 'discountTiers'])
            ->findOrFail($id);

        return $this->successResponse($pricingRule, 'Pricing rule retrieved successfully');
    }

    /**
     * Update the specified pricing rule
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $pricingRule = PricingRule::findOrFail($id);

        $validated = $request->validate([
            'name' => 'string|max:255',
            'code' => 'string|max:255|unique:pricing_rules,code,' . $id . ',id,tenant_id,' . auth()->user()->tenant_id,
            'description' => 'nullable|string',
            'rule_type' => 'in:product,category,customer,customer_group,seasonal,promotional',
            'is_active' => 'boolean',
            'product_id' => 'nullable|exists:products,id',
            'category_id' => 'nullable|exists:product_categories,id',
            'customer_id' => 'nullable|exists:customers,id',
            'customer_group' => 'nullable|string|max:255',
            'priority' => 'integer|min:0',
            'valid_from' => 'nullable|date',
            'valid_to' => 'nullable|date|after_or_equal:valid_from',
            'time_from' => 'nullable|date_format:H:i:s',
            'time_to' => 'nullable|date_format:H:i:s',
            'days_of_week' => 'nullable|array',
            'days_of_week.*' => 'integer|min:0|max:6',
            'min_quantity' => 'nullable|numeric|min:0',
            'max_quantity' => 'nullable|numeric|min:0',
            'pricing_method' => 'in:fixed,markup,markdown,discount',
            'adjustment_type' => 'in:flat,percentage',
            'adjustment_value' => 'numeric|min:0',
            'fixed_price' => 'nullable|numeric|min:0',
            'can_compound' => 'boolean',
            'exclude_rules' => 'nullable|array',
            'conditions' => 'nullable|array',
        ]);

        DB::beginTransaction();
        try {
            $validated['updated_by'] = auth()->id();
            $pricingRule->update($validated);

            DB::commit();

            return $this->successResponse(
                $pricingRule->fresh(['product', 'category', 'customer']),
                'Pricing rule updated successfully'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to update pricing rule: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Remove the specified pricing rule
     */
    public function destroy(int $id): JsonResponse
    {
        $pricingRule = PricingRule::findOrFail($id);

        DB::beginTransaction();
        try {
            $pricingRule->delete();
            DB::commit();

            return $this->successResponse(null, 'Pricing rule deleted successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to delete pricing rule: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Activate a pricing rule
     */
    public function activate(int $id): JsonResponse
    {
        $pricingRule = PricingRule::findOrFail($id);

        $pricingRule->update([
            'is_active' => true,
            'updated_by' => auth()->id(),
        ]);

        return $this->successResponse($pricingRule, 'Pricing rule activated successfully');
    }

    /**
     * Deactivate a pricing rule
     */
    public function deactivate(int $id): JsonResponse
    {
        $pricingRule = PricingRule::findOrFail($id);

        $pricingRule->update([
            'is_active' => false,
            'updated_by' => auth()->id(),
        ]);

        return $this->successResponse($pricingRule, 'Pricing rule deactivated successfully');
    }
}
