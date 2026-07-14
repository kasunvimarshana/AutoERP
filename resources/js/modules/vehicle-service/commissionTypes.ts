import type { NamedResource } from '@/shared/types/common';
import type { CommissionType, VehicleServiceJobLine } from './vehicleServiceTypes';

export const vehicleServiceWorkforceRoles = ['technician', 'helper', 'inspector', 'custom'] as const;
export type VehicleServiceWorkforceRole = typeof vehicleServiceWorkforceRoles[number];

export interface VehicleServiceCommissionDefault {
    commission_type: CommissionType;
    commission_value: string;
}

export interface VehicleServiceSupervisorCommissionPolicy extends VehicleServiceCommissionDefault {
    id: number;
    row_version: number;
    is_active: boolean;
}

export interface VehicleServiceLaborItemCommissionRule extends VehicleServiceCommissionDefault {
    id: number;
    row_version: number;
    role_type: VehicleServiceWorkforceRole;
    is_active: boolean;
    item: NamedResource | null;
}

export interface VehicleServiceCommissionPolicyPayload extends VehicleServiceCommissionDefault {
    expected_version?: number;
    is_active: boolean;
}

export type CommissionAwareVehicleServiceJobLine = VehicleServiceJobLine & {
    commission_defaults?: Partial<Record<VehicleServiceWorkforceRole, VehicleServiceCommissionDefault>>;
};
