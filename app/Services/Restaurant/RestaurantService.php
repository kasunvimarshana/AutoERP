<?php

namespace App\Services\Restaurant;

use App\Models\Restaurant\Booking;
use App\Models\Restaurant\ModifierSet;
use App\Models\Restaurant\RestaurantTable;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class RestaurantService
{
    // ── Restaurant Tables ──────────────────────────────────────────────

    public function paginateTables(string $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = RestaurantTable::where('tenant_id', $tenantId);

        if (isset($filters['business_location_id'])) {
            $query->where('business_location_id', $filters['business_location_id']);
        }
        if (isset($filters['is_active'])) {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    public function createTable(array $data): RestaurantTable
    {
        return RestaurantTable::create($data);
    }

    public function updateTable(string $id, array $data): RestaurantTable
    {
        $table = RestaurantTable::findOrFail($id);
        $table->update($data);

        return $table->fresh();
    }

    public function deleteTable(string $id): void
    {
        RestaurantTable::findOrFail($id)->delete();
    }

    // ── Modifier Sets ──────────────────────────────────────────────────

    public function paginateModifierSets(string $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = ModifierSet::where('tenant_id', $tenantId)->with('options');

        if (isset($filters['business_location_id'])) {
            $query->where('business_location_id', $filters['business_location_id']);
        }
        if (isset($filters['is_active'])) {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    public function createModifierSet(array $data): ModifierSet
    {
        return DB::transaction(function () use ($data) {
            $options = $data['options'] ?? [];
            unset($data['options']);

            $modifierSet = ModifierSet::create($data);

            foreach ($options as $option) {
                $modifierSet->options()->create($option);
            }

            return $modifierSet->fresh(['options']);
        });
    }

    public function updateModifierSet(string $id, array $data): ModifierSet
    {
        return DB::transaction(function () use ($id, $data) {
            $modifierSet = ModifierSet::findOrFail($id);
            $options = $data['options'] ?? null;
            unset($data['options']);

            $modifierSet->update($data);

            if ($options !== null) {
                $modifierSet->options()->delete();
                foreach ($options as $option) {
                    $modifierSet->options()->create($option);
                }
            }

            return $modifierSet->fresh(['options']);
        });
    }

    public function deleteModifierSet(string $id): void
    {
        ModifierSet::findOrFail($id)->delete();
    }

    // ── Bookings ───────────────────────────────────────────────────────

    public function paginateBookings(string $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Booking::where('tenant_id', $tenantId)
            ->with(['restaurantTable', 'customer', 'waiter']);

        if (isset($filters['business_location_id'])) {
            $query->where('business_location_id', $filters['business_location_id']);
        }
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (isset($filters['date'])) {
            $query->whereDate('booking_start', $filters['date']);
        }

        return $query->orderBy('booking_start')->paginate($perPage);
    }

    public function createBooking(array $data): Booking
    {
        return Booking::create($data);
    }

    public function updateBooking(string $id, array $data): Booking
    {
        $booking = Booking::findOrFail($id);
        $booking->update($data);

        return $booking->fresh(['restaurantTable', 'customer', 'waiter']);
    }

    public function deleteBooking(string $id): void
    {
        Booking::findOrFail($id)->delete();
    }
}
