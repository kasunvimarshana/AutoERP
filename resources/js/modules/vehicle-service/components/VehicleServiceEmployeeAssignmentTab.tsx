import { useState } from 'react';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { ConfirmDialog } from '@/shared/components/ConfirmDialog';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { useApi } from '@/shared/hooks/useApi';
import {
    createVehicleServiceEmployee,
    deleteVehicleServiceEmployee,
    listEmployeeAssignableLines,
    updateVehicleServiceEmployee,
} from '../vehicleServiceApi';
import type { VehicleServiceEmployeeAssignment, VehicleServiceJobLine } from '../vehicleServiceTypes';
import {
    assignmentFormToPayload,
    assignmentToForm,
    emptyAssignmentForm,
    type AssignmentDialogState,
    type AssignmentFormValue,
    type AssignmentRow,
} from './employee-assignment/assignmentForm';
import { EmployeeAssignmentDialog } from './employee-assignment/EmployeeAssignmentDialog';
import { EmployeeAssignmentTable } from './employee-assignment/EmployeeAssignmentTable';

export default function VehicleServiceEmployeeAssignmentTab({
    jobId,
    expectedVersion,
    onChanged,
}: {
    jobId: number;
    expectedVersion: number;
    onChanged: (nextVersion: number) => void;
}) {
    const result = useApi((signal) => listEmployeeAssignableLines(jobId, signal), [jobId]);
    const [dialog, setDialog] = useState<AssignmentDialogState | null>(null);
    const [removeTarget, setRemoveTarget] = useState<AssignmentRow | null>(null);
    const [saving, setSaving] = useState(false);
    const [removing, setRemoving] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const assignments = (result.data ?? []).flatMap((line) =>
        (line.employee_assignments ?? []).map((assignment) => ({ ...assignment, line })));

    const saveAssignment = async (value: AssignmentFormValue) => {
        if (!dialog || value.lineId === null || !value.employee || saving) return;
        setSaving(true);
        setError(null);
        try {
            const payload = { ...assignmentFormToPayload(value), expected_version: expectedVersion };
            if (dialog.mode === 'edit') {
                const saved = await updateVehicleServiceEmployee(
                    jobId,
                    value.lineId,
                    dialog.assignmentId,
                    payload,
                );
                result.setData((current) => replaceAssignment(current ?? [], value.lineId!, saved));
            } else {
                const saved = await createVehicleServiceEmployee(jobId, value.lineId, payload);
                result.setData((current) => appendAssignment(current ?? [], value.lineId!, saved));
            }
            setDialog(null);
            onChanged(expectedVersion + 1);
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setSaving(false);
        }
    };

    const removeAssignment = async (row: AssignmentRow) => {
        if (removing) return;
        setRemoving(true);
        setError(null);
        try {
            await deleteVehicleServiceEmployee(jobId, row.line.id, row.id, expectedVersion);
            setRemoveTarget(null);
            result.setData((current) => removeAssignmentFromLines(current ?? [], row.line.id, row.id));
            onChanged(expectedVersion + 1);
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setRemoving(false);
        }
    };

    return (
        <div className="space-y-5">
            <ErrorAlert error={error ?? result.error} />
            <EmployeeAssignmentTable
                loading={result.loading}
                rows={assignments}
                onAdd={() => {
                    setError(null);
                    setDialog({ mode: 'create', value: emptyAssignmentForm() });
                }}
                onEdit={(row) => {
                    setError(null);
                    setDialog({
                        mode: 'edit',
                        assignmentId: row.id,
                        value: assignmentToForm(row),
                    });
                }}
                onRemove={setRemoveTarget}
            />
            <EmployeeAssignmentDialog
                dialog={dialog}
                lines={result.data ?? []}
                error={error}
                saving={saving}
                onClose={() => setDialog(null)}
                onSave={(value) => void saveAssignment(value)}
            />
            <ConfirmDialog
                open={Boolean(removeTarget)}
                title="Remove assignment"
                message="This employee assignment will be removed from the service line."
                confirmLabel="Remove assignment"
                loading={removing}
                onCancel={() => !removing && setRemoveTarget(null)}
                onConfirm={() => removeTarget && void removeAssignment(removeTarget)}
            />
        </div>
    );
}

function replaceAssignment(lines: VehicleServiceJobLine[], lineId: number, assignment: VehicleServiceEmployeeAssignment): VehicleServiceJobLine[] {
    return lines.map((line) => line.id !== lineId
        ? line
        : {
            ...line,
            employee_assignments: (line.employee_assignments ?? []).map((current) =>
                current.id === assignment.id ? assignment : current),
        });
}

function appendAssignment(lines: VehicleServiceJobLine[], lineId: number, assignment: VehicleServiceEmployeeAssignment): VehicleServiceJobLine[] {
    return lines.map((line) => line.id !== lineId
        ? line
        : {
            ...line,
            employee_assignments: [...(line.employee_assignments ?? []), assignment],
        });
}

function removeAssignmentFromLines(lines: VehicleServiceJobLine[], lineId: number, assignmentId: number): VehicleServiceJobLine[] {
    return lines.map((line) => line.id !== lineId
        ? line
        : {
            ...line,
            employee_assignments: (line.employee_assignments ?? []).filter((assignment) => assignment.id !== assignmentId),
        });
}
