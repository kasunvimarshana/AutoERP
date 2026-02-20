<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PosTransaction;
use App\Services\Restaurant\RestaurantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RestaurantController extends Controller
{
    public function __construct(
        private readonly RestaurantService $restaurantService
    ) {}

    // ── Restaurant Tables ──────────────────────────────────────────────

    public function indexTables(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $perPage = min((int) $request->query('per_page', 50), 100);
        $filters = $request->only(['business_location_id', 'is_active']);

        return response()->json($this->restaurantService->paginateTables($tenantId, $filters, $perPage));
    }

    public function storeTable(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('settings.manage'), 403);

        $data = $request->validate([
            'business_location_id' => ['required', 'uuid', 'exists:business_locations,id'],
            'name' => ['required', 'string', 'max:100'],
            'capacity' => ['sometimes', 'integer', 'min:1'],
            'description' => ['sometimes', 'nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $data['tenant_id'] = $request->user()->tenant_id;

        return response()->json($this->restaurantService->createTable($data), 201);
    }

    public function updateTable(Request $request, string $id): JsonResponse
    {
        abort_unless($request->user()?->can('settings.manage'), 403);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'capacity' => ['sometimes', 'integer', 'min:1'],
            'description' => ['sometimes', 'nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        return response()->json($this->restaurantService->updateTable($id, $data));
    }

    public function destroyTable(Request $request, string $id): JsonResponse
    {
        abort_unless($request->user()?->can('settings.manage'), 403);
        $this->restaurantService->deleteTable($id);

        return response()->json(null, 204);
    }

    // ── Modifier Sets ──────────────────────────────────────────────────

    public function indexModifierSets(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $perPage = min((int) $request->query('per_page', 50), 100);
        $filters = $request->only(['business_location_id', 'is_active']);

        return response()->json($this->restaurantService->paginateModifierSets($tenantId, $filters, $perPage));
    }

    public function storeModifierSet(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('products.manage'), 403);

        $data = $request->validate([
            'business_location_id' => ['required', 'uuid', 'exists:business_locations,id'],
            'name' => ['required', 'string', 'max:150'],
            'type' => ['sometimes', 'string', 'in:optional,required,single,multiple'],
            'is_active' => ['sometimes', 'boolean'],
            'options' => ['sometimes', 'array'],
            'options.*.name' => ['required_with:options', 'string', 'max:150'],
            'options.*.price' => ['sometimes', 'numeric', 'min:0'],
            'options.*.is_active' => ['sometimes', 'boolean'],
            'options.*.sort_order' => ['sometimes', 'integer', 'min:0'],
        ]);

        $data['tenant_id'] = $request->user()->tenant_id;

        return response()->json($this->restaurantService->createModifierSet($data), 201);
    }

    public function updateModifierSet(Request $request, string $id): JsonResponse
    {
        abort_unless($request->user()?->can('products.manage'), 403);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:150'],
            'type' => ['sometimes', 'string', 'in:optional,required,single,multiple'],
            'is_active' => ['sometimes', 'boolean'],
            'options' => ['sometimes', 'array'],
            'options.*.name' => ['required_with:options', 'string', 'max:150'],
            'options.*.price' => ['sometimes', 'numeric', 'min:0'],
            'options.*.is_active' => ['sometimes', 'boolean'],
            'options.*.sort_order' => ['sometimes', 'integer', 'min:0'],
        ]);

        return response()->json($this->restaurantService->updateModifierSet($id, $data));
    }

    public function destroyModifierSet(Request $request, string $id): JsonResponse
    {
        abort_unless($request->user()?->can('products.manage'), 403);
        $this->restaurantService->deleteModifierSet($id);

        return response()->json(null, 204);
    }

    // ── Bookings ───────────────────────────────────────────────────────

    public function indexBookings(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $perPage = min((int) $request->query('per_page', 30), 100);
        $filters = $request->only(['business_location_id', 'status', 'date']);

        return response()->json($this->restaurantService->paginateBookings($tenantId, $filters, $perPage));
    }

    public function storeBooking(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('pos.manage'), 403);

        $data = $request->validate([
            'business_location_id' => ['required', 'uuid', 'exists:business_locations,id'],
            'restaurant_table_id' => ['sometimes', 'nullable', 'uuid', 'exists:restaurant_tables,id'],
            'customer_id' => ['sometimes', 'nullable', 'uuid', 'exists:contacts,id'],
            'correspondent_id' => ['sometimes', 'nullable', 'uuid', 'exists:users,id'],
            'waiter_id' => ['sometimes', 'nullable', 'uuid', 'exists:users,id'],
            'booking_start' => ['required', 'date'],
            'booking_end' => ['sometimes', 'nullable', 'date', 'after:booking_start'],
            'no_of_persons' => ['sometimes', 'integer', 'min:1'],
            'status' => ['sometimes', 'string', 'in:booked,seated,completed,cancelled'],
            'note' => ['sometimes', 'nullable', 'string'],
        ]);

        $data['tenant_id'] = $request->user()->tenant_id;

        return response()->json($this->restaurantService->createBooking($data), 201);
    }

    public function updateBooking(Request $request, string $id): JsonResponse
    {
        abort_unless($request->user()?->can('pos.manage'), 403);

        $data = $request->validate([
            'restaurant_table_id' => ['sometimes', 'nullable', 'uuid', 'exists:restaurant_tables,id'],
            'customer_id' => ['sometimes', 'nullable', 'uuid', 'exists:contacts,id'],
            'correspondent_id' => ['sometimes', 'nullable', 'uuid', 'exists:users,id'],
            'waiter_id' => ['sometimes', 'nullable', 'uuid', 'exists:users,id'],
            'booking_start' => ['sometimes', 'date'],
            'booking_end' => ['sometimes', 'nullable', 'date'],
            'no_of_persons' => ['sometimes', 'integer', 'min:1'],
            'status' => ['sometimes', 'string', 'in:booked,seated,completed,cancelled'],
            'note' => ['sometimes', 'nullable', 'string'],
        ]);

        return response()->json($this->restaurantService->updateBooking($id, $data));
    }

    public function destroyBooking(Request $request, string $id): JsonResponse
    {
        abort_unless($request->user()?->can('pos.manage'), 403);
        $this->restaurantService->deleteBooking($id);

        return response()->json(null, 204);
    }

    // ── Kitchen Display ────────────────────────────────────────────────

    /** List pending kitchen orders (res_order_status = received). */
    public function indexKitchenOrders(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('pos.view'), 403);

        $tenantId = $request->user()->tenant_id;
        $filters = $request->only(['business_location_id', 'res_order_status']);

        $query = PosTransaction::where('tenant_id', $tenantId)
            ->whereNotNull('res_order_status')
            ->with(['restaurantTable:id,name', 'lines.product:id,name']);

        if (! empty($filters['business_location_id'])) {
            $query->where('business_location_id', $filters['business_location_id']);
        }
        if (! empty($filters['res_order_status'])) {
            $query->where('res_order_status', $filters['res_order_status']);
        } else {
            $query->whereIn('res_order_status', ['received', 'cooked']);
        }

        return response()->json($query->orderBy('created_at')->get());
    }

    /** Mark a POS transaction order as cooked (kitchen is done). */
    public function markAsCooked(Request $request, string $id): JsonResponse
    {
        abort_unless($request->user()?->can('pos.manage'), 403);

        $transaction = PosTransaction::where('tenant_id', $request->user()->tenant_id)
            ->whereNotNull('res_order_status')
            ->findOrFail($id);

        $transaction->update(['res_order_status' => 'cooked']);

        return response()->json($transaction->fresh());
    }

    /** Mark a POS transaction order as served (waiter delivered). */
    public function markAsServed(Request $request, string $id): JsonResponse
    {
        abort_unless($request->user()?->can('pos.manage'), 403);

        $transaction = PosTransaction::where('tenant_id', $request->user()->tenant_id)
            ->whereNotNull('res_order_status')
            ->findOrFail($id);

        $transaction->update(['res_order_status' => 'served']);

        return response()->json($transaction->fresh());
    }
}
