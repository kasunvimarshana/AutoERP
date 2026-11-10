<?php

declare(strict_types=1);

namespace Modules\Promotion\Services;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\Item\Models\Item;
use Modules\Promotion\Enums\PromotionCountScope;
use Modules\Promotion\Enums\PromotionStatus;
use Modules\Vehicle\Enums\VehicleOwnerType;
use Modules\VehicleService\Services\VehicleServiceLineItemLookupService;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class PromotionOfferService
{
    private const ACTION_CREATED = 'created';

    private const ACTION_DELETED = 'deleted';

    public function __construct(private readonly VehicleServiceLineItemLookupService $jobItems) {}

    public function list(int $tenantId, ?int $organizationUnitId): array
    {
        return DB::table('promotion_offers as offer')
            ->join('items as qualifying', 'qualifying.id', '=', 'offer.qualifying_item_id')
            ->join('items as reward', 'reward.id', '=', 'offer.reward_item_id')
            ->where('offer.tenant_id', $tenantId)
            ->where(function ($query) use ($organizationUnitId): void {
                $query->whereNull('offer.organization_unit_id')->orWhere('offer.organization_unit_id', $organizationUnitId);
            })
            ->orderByDesc('offer.id')
            ->get(['offer.*', 'qualifying.code as qualifying_item_code',
                'qualifying.name as qualifying_item_name', 'reward.name as reward_item_name'])
            ->map(static function ($offer): array {
                $row = (array) $offer;
                $row['qualifying_item'] = ['id' => (int) $offer->qualifying_item_id,
                    'code' => $offer->qualifying_item_code, 'name' => $offer->qualifying_item_name];
                $row['reward_item'] = ['id' => (int) $offer->reward_item_id, 'name' => $offer->reward_item_name];

                return $row;
            })
            ->all();
    }

    public function create(int $tenantId, ?int $organizationUnitId, int $actorId, array $input): array
    {
        if (trim((string) $input['code']) === '' || trim((string) $input['name']) === ''
            || (int) $input['paid_quantity'] < 1 || (int) $input['reward_quantity'] < 1
            || (isset($input['ends_on']) && $input['starts_on'] > $input['ends_on'])
            || PromotionCountScope::tryFrom((string) $input['count_scope']) === null) {
            throw new InvalidArgumentException('Enter a valid offer name, period, scope, and positive visit counts.');
        }
        $this->jobItem($tenantId, $organizationUnitId, (int) $input['qualifying_item_id']);
        $this->jobItem($tenantId, $organizationUnitId, (int) $input['reward_item_id']);
        try {
            $id = DB::transaction(function () use ($tenantId, $organizationUnitId, $actorId, $input): int {
                $offerId = DB::table('promotion_offers')->insertGetId([
                    'tenant_id' => $tenantId,
                    'organization_unit_id' => $organizationUnitId,
                    'code' => Str::upper(trim((string) $input['code'])),
                    'name' => trim((string) $input['name']),
                    'qualifying_item_id' => (int) $input['qualifying_item_id'],
                    'paid_quantity' => (int) $input['paid_quantity'],
                    'reward_item_id' => (int) $input['reward_item_id'],
                    'reward_quantity' => (int) $input['reward_quantity'],
                    'count_scope' => (string) $input['count_scope'],
                    'starts_on' => (string) $input['starts_on'],
                    'ends_on' => $input['ends_on'] ?? null,
                    'status' => PromotionStatus::Active->value,
                    'row_version' => 1,
                    'created_by' => $actorId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $offer = DB::table('promotion_offers')->where('tenant_id', $tenantId)->find($offerId);
                $this->recordLifecycle($tenantId, $offer, self::ACTION_CREATED, $actorId, null, PromotionStatus::Active->value);

                return $offerId;
            });
        } catch (UniqueConstraintViolationException) {
            throw new ConflictHttpException('This offer code is already in use.');
        }

        return $this->find($tenantId, $id);
    }

    public function assign(int $tenantId, ?int $organizationUnitId, int $actorId, int $offerId, array $input): array
    {
        try {
            return DB::transaction(function () use ($tenantId, $organizationUnitId, $actorId, $offerId, $input): array {
                $offer = DB::table('promotion_offers')->where('tenant_id', $tenantId)->lockForUpdate()->find($offerId);
                if ($offer === null || $offer->status !== PromotionStatus::Active->value) {
                    throw new InvalidArgumentException('Select an active offer.');
                }
                $this->assertBranch($offer->organization_unit_id, $organizationUnitId);
                if ($offer->ends_on !== null && (string) $offer->ends_on < now()->toDateString()) {
                    throw new InvalidArgumentException('This offer has expired.');
                }
                $customerId = (int) $input['customer_id'];
                $this->customer($tenantId, $customerId);
                $vehicleId = isset($input['vehicle_id']) ? (int) $input['vehicle_id'] : null;
                if ($offer->count_scope === PromotionCountScope::Vehicle->value) {
                    if ($vehicleId === null) {
                        throw new InvalidArgumentException('Select a vehicle for this offer.');
                    }
                    $this->customerVehicle($tenantId, $organizationUnitId, $customerId, $vehicleId);
                } else {
                    $vehicleId = null;
                }
                $startsOn = max((string) $offer->starts_on, now()->toDateString());
                $this->assertNoOverlappingAssignment(
                    $tenantId, $offerId, $customerId, $vehicleId, $startsOn, $offer->ends_on,
                );
                $id = DB::table('promotion_offer_assignments')->insertGetId([
                    'tenant_id' => $tenantId,
                    'offer_id' => $offerId,
                    'customer_id' => $customerId,
                    'vehicle_id' => $vehicleId,
                    'card_id' => null,
                    'starts_on' => $startsOn,
                    'ends_on' => $offer->ends_on,
                    'status' => PromotionStatus::Active->value,
                    'row_version' => 1,
                    'assigned_by' => $actorId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return (array) DB::table('promotion_offer_assignments')->where('tenant_id', $tenantId)->find($id);
            });
        } catch (UniqueConstraintViolationException) {
            throw new ConflictHttpException('This offer is already assigned.');
        }
    }

    public function find(int $tenantId, int $offerId): array
    {
        $offer = DB::table('promotion_offers')->where('tenant_id', $tenantId)->find($offerId);
        if ($offer === null) {
            abort(404);
        }

        return (array) $offer;
    }

    public function changeStatus(int $tenantId, ?int $organizationUnitId, int $actorId, int $offerId, int $version, string $status): array
    {
        return DB::transaction(function () use ($tenantId, $organizationUnitId, $actorId, $offerId, $version, $status): array {
            $offer = DB::table('promotion_offers')->where('tenant_id', $tenantId)->lockForUpdate()->find($offerId);
            if ($offer === null) {
                abort(404);
            }
            $this->assertBranch($offer->organization_unit_id, $organizationUnitId);
            if ((int) $offer->row_version !== $version) {
                throw new ConflictHttpException('Offer changed. Refresh and try again.');
            }
            if ($offer->status === $status) {
                throw new ConflictHttpException('The offer already has this status.');
            }
            if ($status === PromotionStatus::Active->value && $offer->ends_on !== null && $offer->ends_on < now()->toDateString()) {
                throw new ConflictHttpException('An expired offer cannot be reactivated.');
            }
            DB::table('promotion_offers')->where('id', $offerId)->where('tenant_id', $tenantId)->update([
                'status' => $status, 'row_version' => $version + 1, 'updated_at' => now(),
            ]);
            $this->recordLifecycle($tenantId, $offer, $status, $actorId, $offer->status, $status);

            return $this->find($tenantId, $offerId);
        });
    }

    public function deleteUnused(int $tenantId, ?int $organizationUnitId, int $actorId, int $offerId, int $version): void
    {
        DB::transaction(function () use ($tenantId, $organizationUnitId, $actorId, $offerId, $version): void {
            $offer = DB::table('promotion_offers')->where('tenant_id', $tenantId)->lockForUpdate()->find($offerId);
            if ($offer === null) {
                abort(404);
            }
            $this->assertBranch($offer->organization_unit_id, $organizationUnitId);
            if ((int) $offer->row_version !== $version) {
                throw new ConflictHttpException('Offer changed. Refresh and try again.');
            }
            if (DB::table('promotion_offer_assignments')->where('tenant_id', $tenantId)->where('offer_id', $offerId)->exists()
                || DB::table('promotion_card_benefits')->where('tenant_id', $tenantId)->where('offer_id', $offerId)->exists()) {
                throw new ConflictHttpException('This offer has assignments or card benefits. Deactivate it instead.');
            }
            $this->recordLifecycle($tenantId, $offer, self::ACTION_DELETED, $actorId, $offer->status, null);
            DB::table('promotion_offers')->where('tenant_id', $tenantId)->where('id', $offerId)->delete();
        });
    }

    public function jobItem(int $tenantId, ?int $organizationUnitId, int $itemId): Item
    {
        $item = Item::query()->forTenant($tenantId)->findOrFail($itemId);
        if (! $this->jobItems->isSupportedItem($item) || ! $item->is_active
            || ($item->organization_unit_id !== null && (int) $item->organization_unit_id !== $organizationUnitId)) {
            throw new InvalidArgumentException('Choose an active vehicle service job item available to this branch.');
        }

        return $item;
    }

    public function customer(int $tenantId, int $customerId): void
    {
        if (! DB::table('customers')->where('tenant_id', $tenantId)->whereNull('deleted_at')->where('id', $customerId)->exists()) {
            throw new InvalidArgumentException('Choose an available customer.');
        }
    }

    public function customerVehicle(int $tenantId, ?int $organizationUnitId, int $customerId, int $vehicleId): void
    {
        $vehicle = DB::table('vehicles')->where('tenant_id', $tenantId)->whereNull('deleted_at')->where('id', $vehicleId)->first();
        if ($vehicle === null || ($vehicle->organization_unit_id !== null && (int) $vehicle->organization_unit_id !== $organizationUnitId)) {
            throw new InvalidArgumentException('Choose a vehicle available to this branch.');
        }
        if (! DB::table('vehicle_ownerships')->where('tenant_id', $tenantId)->where('vehicle_id', $vehicleId)
            ->where('owner_type', VehicleOwnerType::Customer->value)->where('owner_id', $customerId)->where('is_current', true)->exists()) {
            throw new InvalidArgumentException('The selected vehicle is not currently owned by this customer.');
        }
    }

    public function assertNoOverlappingAssignment(
        int $tenantId,
        int $offerId,
        int $customerId,
        ?int $vehicleId,
        string $startsOn,
        ?string $endsOn,
    ): void {
        $existing = DB::table('promotion_offer_assignments')
            ->where('tenant_id', $tenantId)
            ->where('offer_id', $offerId)
            ->when($vehicleId === null,
                fn ($query) => $query->where('customer_id', $customerId)->whereNull('vehicle_id'),
                fn ($query) => $query->where('vehicle_id', $vehicleId))
            ->where('status', PromotionStatus::Active->value)
            ->when($endsOn !== null, fn ($query) => $query->where('starts_on', '<=', $endsOn))
            ->where(fn ($query) => $query->whereNull('ends_on')->orWhere('ends_on', '>=', $startsOn))
            ->lockForUpdate()
            ->exists();

        if ($existing) {
            throw new ConflictHttpException('An active offer already covers this customer or vehicle during the selected period.');
        }
    }

    public function assertBranch(?int $offerBranchId, ?int $organizationUnitId): void
    {
        if ($offerBranchId !== null && (int) $offerBranchId !== $organizationUnitId) {
            throw new InvalidArgumentException('This promotion is not available at this branch.');
        }
    }

    private function recordLifecycle(
        int $tenantId,
        object $offer,
        string $action,
        int $actorId,
        ?string $oldStatus,
        ?string $newStatus,
    ): void {
        $this->scoped('promotion_offer_lifecycle_events', $tenantId)->insert([
            'tenant_id' => $tenantId,
            'offer_id' => $offer->id,
            'offer_code' => $offer->code,
            'offer_name' => $offer->name,
            'action' => $action,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'actor_id' => $actorId,
            'occurred_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function scoped(string $table, int $tenantId): \Illuminate\Database\Query\Builder
    {
        return DB::table($table)->where('tenant_id', $tenantId);
    }
}
