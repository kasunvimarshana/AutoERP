<?php

declare(strict_types=1);

namespace Modules\VehicleService\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Item\Enums\ItemType;
use Modules\Item\Models\Item;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use Modules\VehicleService\Enums\VehicleServiceCommissionType;
use Modules\VehicleService\Enums\VehicleServiceWorkforceRole;
use Modules\VehicleService\Models\VehicleServiceLaborItemCommissionRule;
use Modules\VehicleService\Models\VehicleServiceSupervisorCommissionPolicy;

final class VehicleServiceCommissionPolicyService
{
    private const ZERO_AMOUNT = '0.000000';
    private const VALIDATION_BASE = '100.000000';

    public function __construct(private readonly VehicleServiceCommissionService $commissions) {}

    public function supervisorDefault(
        int $tenantId,
        int $organizationUnitId,
    ): ?VehicleServiceSupervisorCommissionPolicy {
        return VehicleServiceSupervisorCommissionPolicy::query()
            ->where('tenant_id', $tenantId)
            ->where('organization_unit_id', $organizationUnitId)
            ->first();
    }

    public function saveSupervisorDefault(
        int $tenantId,
        int $organizationUnitId,
        VehicleServiceCommissionType $type,
        string $value,
        bool $isActive,
        ?int $expectedVersion,
        ?int $actorId,
    ): VehicleServiceSupervisorCommissionPolicy {
        return DB::transaction(function () use (
            $tenantId,
            $organizationUnitId,
            $type,
            $value,
            $isActive,
            $expectedVersion,
            $actorId,
        ): VehicleServiceSupervisorCommissionPolicy {
            $this->lockOrganizationUnit($tenantId, $organizationUnitId);
            $policy = VehicleServiceSupervisorCommissionPolicy::query()
                ->where('tenant_id', $tenantId)
                ->where('organization_unit_id', $organizationUnitId)
                ->lockForUpdate()
                ->first();
            $this->assertExpectedVersion($policy?->row_version, $expectedVersion);
            $normalizedValue = $this->validatedValue($type, $value);

            if (! $policy instanceof VehicleServiceSupervisorCommissionPolicy) {
                $policy = new VehicleServiceSupervisorCommissionPolicy();
                $policy->forceFill([
                    'tenant_id' => $tenantId,
                    'organization_unit_id' => $organizationUnitId,
                    'row_version' => 1,
                    'created_by' => $actorId,
                ]);
            } else {
                $policy->row_version = (int) $policy->row_version + 1;
            }

            $policy->forceFill([
                'commission_type' => $type->value,
                'commission_value' => $normalizedValue,
                'is_active' => $isActive,
                'updated_by' => $actorId,
            ])->save();

            return $policy->refresh();
        });
    }

    public function laborRule(
        int $tenantId,
        int $organizationUnitId,
        int $itemId,
        VehicleServiceWorkforceRole $role,
    ): ?VehicleServiceLaborItemCommissionRule {
        return VehicleServiceLaborItemCommissionRule::query()
            ->where('tenant_id', $tenantId)
            ->where('organization_unit_id', $organizationUnitId)
            ->where('item_id', $itemId)
            ->where('role_type', $role->value)
            ->with('item')
            ->first();
    }

    public function saveLaborRule(
        int $tenantId,
        int $organizationUnitId,
        int $itemId,
        VehicleServiceWorkforceRole $role,
        VehicleServiceCommissionType $type,
        string $value,
        bool $isActive,
        ?int $expectedVersion,
        ?int $actorId,
    ): VehicleServiceLaborItemCommissionRule {
        return DB::transaction(function () use (
            $tenantId,
            $organizationUnitId,
            $itemId,
            $role,
            $type,
            $value,
            $isActive,
            $expectedVersion,
            $actorId,
        ): VehicleServiceLaborItemCommissionRule {
            $this->lockOrganizationUnit($tenantId, $organizationUnitId);
            $item = $this->laborItem($tenantId, $organizationUnitId, $itemId);
            $rule = VehicleServiceLaborItemCommissionRule::query()
                ->where('tenant_id', $tenantId)
                ->where('organization_unit_id', $organizationUnitId)
                ->where('item_id', $itemId)
                ->where('role_type', $role->value)
                ->lockForUpdate()
                ->first();
            $this->assertExpectedVersion($rule?->row_version, $expectedVersion);
            $normalizedValue = $this->validatedValue($type, $value);

            if (! $rule instanceof VehicleServiceLaborItemCommissionRule) {
                $rule = new VehicleServiceLaborItemCommissionRule();
                $rule->forceFill([
                    'tenant_id' => $tenantId,
                    'organization_unit_id' => $organizationUnitId,
                    'item_id' => $item->getKey(),
                    'role_type' => $role->value,
                    'row_version' => 1,
                    'created_by' => $actorId,
                ]);
            } else {
                $rule->row_version = (int) $rule->row_version + 1;
            }

            $rule->forceFill([
                'commission_type' => $type->value,
                'commission_value' => $normalizedValue,
                'is_active' => $isActive,
                'updated_by' => $actorId,
            ])->save();

            return $rule->refresh()->load('item');
        });
    }

    /** @return array{type: VehicleServiceCommissionType, value: string} */
    public function resolveSupervisorDefault(int $tenantId, int $organizationUnitId): array
    {
        $policy = $this->supervisorDefault($tenantId, $organizationUnitId);

        return $policy instanceof VehicleServiceSupervisorCommissionPolicy && $policy->is_active
            ? ['type' => $policy->commission_type, 'value' => (string) $policy->commission_value]
            : $this->none();
    }

    /** @return array{type: VehicleServiceCommissionType, value: string} */
    public function resolveLaborRule(
        int $tenantId,
        int $organizationUnitId,
        int $itemId,
        VehicleServiceWorkforceRole $role,
    ): array {
        $rule = $this->laborRule($tenantId, $organizationUnitId, $itemId, $role);

        return $rule instanceof VehicleServiceLaborItemCommissionRule && $rule->is_active
            ? ['type' => $rule->commission_type, 'value' => (string) $rule->commission_value]
            : $this->none();
    }

    /**
     * @param list<int> $itemIds
     * @return array<int, array<string, array{commission_type: string, commission_value: string}>>
     */
    public function laborDefaultsForItems(
        int $tenantId,
        int $organizationUnitId,
        array $itemIds,
    ): array {
        if ($itemIds === []) {
            return [];
        }

        $defaults = [];
        $rules = VehicleServiceLaborItemCommissionRule::query()
            ->where('tenant_id', $tenantId)
            ->where('organization_unit_id', $organizationUnitId)
            ->whereIn('item_id', array_values(array_unique($itemIds)))
            ->where('is_active', true)
            ->get();

        foreach ($rules as $rule) {
            $defaults[(int) $rule->item_id][$rule->role_type->value] = [
                'commission_type' => $rule->commission_type->value,
                'commission_value' => (string) $rule->commission_value,
            ];
        }

        return $defaults;
    }

    private function lockOrganizationUnit(int $tenantId, int $organizationUnitId): void
    {
        OrganizationUnitModel::query()
            ->where('tenant_id', $tenantId)
            ->whereKey($organizationUnitId)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function laborItem(int $tenantId, int $organizationUnitId, int $itemId): Item
    {
        $item = Item::query()
            ->where('tenant_id', $tenantId)
            ->whereKey($itemId)
            ->where(function ($query) use ($organizationUnitId): void {
                $query->whereNull('organization_unit_id')
                    ->orWhere('organization_unit_id', $organizationUnitId);
            })
            ->firstOrFail();

        if ($item->item_type !== ItemType::Labour) {
            throw new InvalidArgumentException('Commission defaults can only be assigned to labour items.');
        }

        return $item;
    }

    private function validatedValue(VehicleServiceCommissionType $type, string $value): string
    {
        $this->commissions->calculate($type, $value, self::VALIDATION_BASE);

        return $type === VehicleServiceCommissionType::None
            ? self::ZERO_AMOUNT
            : trim($value);
    }

    private function assertExpectedVersion(int|string|null $currentVersion, ?int $expectedVersion): void
    {
        if ($currentVersion === null) {
            if ($expectedVersion !== null) {
                throw new InvalidArgumentException('The commission policy does not exist. Refresh and try again.');
            }

            return;
        }

        if ($expectedVersion === null || (int) $currentVersion !== $expectedVersion) {
            throw new InvalidArgumentException('The commission policy changed since it was loaded. Refresh and try again.');
        }
    }

    /** @return array{type: VehicleServiceCommissionType, value: string} */
    private function none(): array
    {
        return [
            'type' => VehicleServiceCommissionType::None,
            'value' => self::ZERO_AMOUNT,
        ];
    }
}
