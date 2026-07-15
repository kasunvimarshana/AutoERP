import type { NamedResource } from '@/shared/types/common';
import type {
    CommissionType,
    VehicleServiceEmployeeAssignment,
    VehicleServiceEmployeeAssignmentPayload,
    VehicleServiceJobLine,
} from '../../vehicleServiceTypes';
import type {
    CommissionAwareVehicleServiceJobLine,
    VehicleServiceWorkforceRole,
} from '../../commissionTypes';

const ZERO_AMOUNT = '0.000000';
export type AssignmentRow = VehicleServiceEmployeeAssignment & { line: VehicleServiceJobLine };

export type AssignmentDialogState =
    | { mode: 'create'; value: AssignmentFormValue }
    | { mode: 'edit'; assignmentId: number; value: AssignmentFormValue };

export interface AssignmentFormValue {
    lineId: number | null;
    employee: NamedResource | null;
    role: VehicleServiceWorkforceRole;
    hours: string;
    rate: string;
    commissionType: CommissionType;
    commissionValue: string;
    status: 'assigned' | 'completed' | 'cancelled';
}

export function emptyAssignmentForm(): AssignmentFormValue {
    return {
        lineId: null,
        employee: null,
        role: 'technician',
        hours: ZERO_AMOUNT,
        rate: ZERO_AMOUNT,
        commissionType: 'none',
        commissionValue: ZERO_AMOUNT,
        status: 'assigned',
    };
}

export function assignmentToForm(row: AssignmentRow): AssignmentFormValue {
    return {
        lineId: row.line.id,
        employee: row.employee ?? null,
        role: isWorkforceRole(row.role_type) ? row.role_type : 'custom',
        hours: row.assigned_hours,
        rate: row.rate,
        commissionType: row.commission_type,
        commissionValue: row.commission_value,
        status: isAssignmentStatus(row.status) ? row.status : 'assigned',
    };
}

export function applyAssignmentCommissionDefault(
    current: AssignmentFormValue,
    lines: VehicleServiceJobLine[],
    lineId: number | null,
): AssignmentFormValue {
    const line = lines.find((candidate) => candidate.id === lineId) as CommissionAwareVehicleServiceJobLine | undefined;
    const commission = line?.commission_default;

    return {
        ...current,
        lineId,
        commissionType: commission?.commission_type ?? 'none',
        commissionValue: commission?.commission_value ?? ZERO_AMOUNT,
    };
}

export function assignmentFormToPayload(
    value: AssignmentFormValue,
): VehicleServiceEmployeeAssignmentPayload {
    return {
        employee_id: value.employee?.id ?? 0,
        role_type: value.role,
        assigned_hours: value.hours,
        rate: value.rate,
        commission_type: value.commissionType,
        commission_value: value.commissionValue,
        status: value.status,
    };
}

export function formatCommissionSummary(row: AssignmentRow): string {
    if (row.commission_type === 'none') return 'None';
    return `${row.commission_type}: ${row.commission_amount}`;
}

function isAssignmentStatus(
    value: string,
): value is AssignmentFormValue['status'] {
    return ['assigned', 'completed', 'cancelled'].includes(value);
}

function isWorkforceRole(value: string): value is VehicleServiceWorkforceRole {
    return ['technician', 'helper', 'inspector', 'custom'].includes(value);
}
