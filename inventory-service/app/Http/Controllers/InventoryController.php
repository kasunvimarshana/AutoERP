<?php

namespace App\Http\Controllers;

use App\Models\InventoryReservation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function reservations(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 15);
        $perPage = max(1, min(100, $perPage));

        $query = InventoryReservation::query()
            ->with('product:id,sku,name')
            ->orderByDesc('created_at');

        if ($request->query('order_id')) {
            $query->where('order_id', $request->query('order_id'));
        }

        if ($request->query('status')) {
            $query->where('status', $request->query('status'));
        }

        $reservations = $query->paginate($perPage);

        return response()->json([
            'data' => $reservations->items(),
            'meta' => [
                'current_page' => $reservations->currentPage(),
                'last_page'    => $reservations->lastPage(),
                'per_page'     => $reservations->perPage(),
                'total'        => $reservations->total(),
            ],
        ]);
    }

    public function reservation(string $id): JsonResponse
    {
        $reservation = InventoryReservation::with('product:id,sku,name')->find($id);

        if (! $reservation) {
            return response()->json(['message' => 'Reservation not found'], 404);
        }

        return response()->json(['data' => $reservation]);
    }
}
