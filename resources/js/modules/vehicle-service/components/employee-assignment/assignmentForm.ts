import type { NamedResource } from '@/shared/types/common';
import type {
    CommissionType,
    VehicleServiceEmployeeAssignment,
    VehicleServiceEmployeeAssignmentPayload,
    VehicleServiceJobLine,
} from '../../vehicleServiceTypes';

export type AssignmentRow = VehicleServiceEmployeeAssignment & { line: VehicleServiceJobLine };

export type AssignmentDialogState =
    | { mode: 'create'; value: AssignmentFormValue }
    | { mode: 'edit'; assignmentId: number; value: AssignmentFormValue };

export interface AssignmentFormValue {
    lineId: number | null;
    employee: NamedResource | null;
    role: string;
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
        hours: '0.000000',
        rate: '0.000000',
        commissionType: 'none',
        commissionValue: '0.000000',
        status: 'assigned',
    };
}

export function assignmentToForm(row: AssignmentRow): AssignmentFormValue {
    return {
        lineId: row.line.id,
        employee: row.employee ?? null,
        role: row.role_type,
        hours: row.assigned_hours,
        rate: row.rate,
        commissionType: row.commission_type,
        commissionValue: row.commission_value,
        status: isAssignmentStatus(row.status) ? row.status : 'assigned',
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
