<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Core\Http\Responses\ApiResponse;
use Modules\Inventory\Http\Resources\ReorderSuggestionResource;
use Modules\Inventory\Models\StockItem;
use Modules\Inventory\Services\ReorderService;

/**
 * Reorder Controller
 *
 * Handles HTTP requests for reorder suggestions and stock level analysis.
 */
class ReorderController extends Controller
{
    public function __construct(
        private ReorderService $reorderService
    ) {}

    /**
     * Get reorder suggestions.
     */
    public function suggestions(Request $request): JsonResponse
    {
        $this->authorize('viewAny', StockItem::class);

        $request->validate([
            'warehouse_id' => ['nullable', 'string', 'exists:warehouses,id'],
            'product_id' => ['nullable', 'string', 'exists:products,id'],
            'priority' => ['nullable', 'string', 'in:high,medium,low'],
        ]);

        $filters = [];

        if ($request->has('warehouse_id')) {
            $filters['warehouse_id'] = $request->warehouse_id;
        }

        if ($request->has('product_id')) {
            $filters['product_id'] = $request->product_id;
        }

        $suggestions = $this->reorderService->generateReorderSuggestions($filters);

        // Filter by priority if requested
        if ($request->has('priority')) {
            $priority = $request->priority;
            $suggestions = $suggestions->filter(function ($suggestion) use ($priority) {
                $level = $suggestion['priority'];

                return match ($priority) {
                    'high' => $level >= 80,
                    'medium' => $level >= 50 && $level < 80,
                    'low' => $level < 50,
                    default => true,
                };
            });
        }

        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);

        $paginatedSuggestions = $suggestions->forPage($page, $perPage);

        $response = [
            'data' => ReorderSuggestionResource::collection($paginatedSuggestions),
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $suggestions->count(),
            'last_page' => ceil($suggestions->count() / $perPage),
        ];

        return ApiResponse::success(
            $response,
            'Reorder suggestions retrieved successfully'
        );
    }

    /**
     * Analyze stock levels for a specific product.
     */
    public function analyzeProduct(Request $request): JsonResponse
    {
        $this->authorize('viewAny', StockItem::class);

        $request->validate([
            'product_id' => ['required', 'string', 'exists:products,id'],
            'warehouse_id' => ['required', 'string', 'exists:warehouses,id'],
        ]);

        $details = $this->reorderService->getReorderDetails(
            $request->product_id,
            $request->warehouse_id
        );

        if (! $details) {
            return ApiResponse::error(
                'Stock item not found for the specified product and warehouse',
                404
            );
        }

        return ApiResponse::success(
            $details,
            'Stock level analysis completed successfully'
        );
    }

    /**
     * Get stock health dashboard data.
     */
    public function stockHealth(Request $request): JsonResponse
    {
        $this->authorize('viewAny', StockItem::class);

        $request->validate([
            'warehouse_id' => ['nullable', 'string', 'exists:warehouses,id'],
        ]);

        $summary = $this->reorderService->getReorderSummary(
            $request->warehouse_id
        );

        return ApiResponse::success(
            $summary,
            'Stock health data retrieved successfully'
        );
    }

    /**
     * Check if a specific product needs reorder.
     */
    public function checkReorder(Request $request): JsonResponse
    {
        $this->authorize('viewAny', StockItem::class);

        $request->validate([
            'product_id' => ['required', 'string', 'exists:products,id'],
            'warehouse_id' => ['required', 'string', 'exists:warehouses,id'],
        ]);

        $needsReorder = $this->reorderService->needsReorder(
            $request->product_id,
            $request->warehouse_id
        );

        $details = null;
        if ($needsReorder) {
            $details = $this->reorderService->getReorderDetails(
                $request->product_id,
                $request->warehouse_id
            );
        }

        return ApiResponse::success(
            [
                'needs_reorder' => $needsReorder,
                'details' => $details,
            ],
            $needsReorder
                ? 'Product requires reorder'
                : 'Product stock level is adequate'
        );
    }
}
