<?php

declare(strict_types=1);

namespace Modules\VehicleService\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\VehicleService\Http\Requests\ListVehicleServiceCommissionPolicyRequest;
use Modules\VehicleService\Http\Requests\SaveVehicleServiceCommissionPolicyRequest;
use Modules\VehicleService\Models\VehicleServiceLaborItemCommissionRule;
use Modules\VehicleService\Models\VehicleServiceSupervisorCommissionPolicy;
use Modules\VehicleService\Services\VehicleServiceCommissionPolicyService;

final class VehicleServiceCommissionPolicyController
{
    public function supervisorDefault(
        ListVehicleServiceCommissionPolicyRequest $request,
        VehicleServiceCommissionPolicyService $service,
    ): JsonResponse {
        $policy = $service->supervisorDefault(
            $request->tenantId(),
            (int) $request->organizationUnitId(),
        );

        return response()->json([
            'data' => $policy instanceof VehicleServiceSupervisorCommissionPolicy
                ? $this->supervisorPayload($policy)
                : null,
        ]);
    }

    public function saveSupervisorDefault(
        SaveVehicleServiceCommissionPolicyRequest $request,
        VehicleServiceCommissionPolicyService $service,
    ): JsonResponse {
        $policy = $service->saveSupervisorDefault(
            $request->tenantId(),
            (int) $request->organizationUnitId(),
            $request->commissionType(),
            $request->commissionValue(),
            $request->isActive(),
            $request->expectedVersion(),
            $request->currentUserId(),
        );

        return response()->json(['data' => $this->supervisorPayload($policy)]);
    }

    public function laborItemRule(
        ListVehicleServiceCommissionPolicyRequest $request,
        int $item,
        VehicleServiceCommissionPolicyService $service,
    ): JsonResponse {
        $rule = $service->laborRule(
            $request->tenantId(),
            (int) $request->organizationUnitId(),
            $item,
        );

        return response()->json([
            'data' => $rule instanceof VehicleServiceLaborItemCommissionRule
                ? $this->laborPayload($rule)
                : null,
        ]);
    }

    public function saveLaborItemRule(
        SaveVehicleServiceCommissionPolicyRequest $request,
        int $item,
        VehicleServiceCommissionPolicyService $service,
    ): JsonResponse {
        $rule = $service->saveLaborRule(
            $request->tenantId(),
            (int) $request->organizationUnitId(),
            $item,
            $request->commissionType(),
            $request->commissionValue(),
            $request->isActive(),
            $request->expectedVersion(),
            $request->currentUserId(),
        );

        return response()->json(['data' => $this->laborPayload($rule)]);
    }

    /** @return array<string, mixed> */
    private function supervisorPayload(VehicleServiceSupervisorCommissionPolicy $policy): array
    {
        return [
            'id' => (int) $policy->getKey(),
            'row_version' => (int) $policy->row_version,
            'commission_type' => $policy->commission_type->value,
            'commission_value' => (string) $policy->commission_value,
            'is_active' => (bool) $policy->is_active,
        ];
    }

    /** @return array<string, mixed> */
    private function laborPayload(VehicleServiceLaborItemCommissionRule $rule): array
    {
        return [
            'id' => (int) $rule->getKey(),
            'row_version' => (int) $rule->row_version,
            'commission_type' => $rule->commission_type->value,
            'commission_value' => (string) $rule->commission_value,
            'is_active' => (bool) $rule->is_active,
            'item' => $rule->relationLoaded('item') && $rule->item !== null ? [
                'id' => (int) $rule->item->getKey(),
                'code' => $rule->item->code,
                'name' => $rule->item->name,
            ] : null,
        ];
    }
}
