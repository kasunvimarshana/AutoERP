import { useState } from 'react';
import { ApiError, hasFieldError, toApiError } from '@/shared/api/apiError';
import { ConfirmDialog } from '@/shared/components/ConfirmDialog';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { useApi } from '@/shared/hooks/useApi';
import {
    createVehicleServiceEmployee,
    deleteVehicleServiceEmployee,
    listEmployeeAssignableLines,
    updateVehicleServiceEmployee,
} from '../vehicleServiceApi';
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

const STALE_VERSION_FIELD = 'expected_version';
const STALE_VERSION_RECOVERY_MESSAGE = 'The service job changed while this request was open. Latest job and workforce data has been loaded. Review and try again.';

export default function VehicleServiceEmployeeAssignmentTab({
    jobId,
    expectedVersion,
    onChanged,
}: {
    jobId: number;
    expectedVersion: number;
    onChanged: () => Promise<void>;
}) {
    const result = useApi((signal) => listEmployeeAssignableLines(jobId, signal), [jobId]);
    const [dialog, setDialog] = useState<AssignmentDialogState | null>(null);
    const [removeTarget, setRemoveTarget] = useState<AssignmentRow | null>(null);
    const [saving, setSaving] = useState(false);
    const [removing, setRemoving] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const assignments = (result.data ?? []).flatMap((line) =>
        (line.employee_assignments ?? []).map((assignment) => ({ ...assignment, line })));

    const synchronize = async () => {
        const [lines] = await Promise.all([
            listEmployeeAssignableLines(jobId),
            onChanged(),
        ]);
        result.setData(lines);
    };

    const handleMutationError = async (requestError: unknown) => {
        const apiError = toApiError(requestError);
        if (!hasFieldError(apiError, STALE_VERSION_FIELD)) {
            setError(apiError);
            return;
        }

        try {
            await synchronize();
            setError(new ApiError(
                STALE_VERSION_RECOVERY_MESSAGE,
                apiError.status,
                apiError.code,
                apiError.type,
            ));
        } catch (refreshError) {
            setError(toApiError(refreshError));
        }
    };

    const saveAssignment = async (value: AssignmentFormValue) => {
        if (!dialog || value.lineId === null || !value.employee || saving) return;
        setSaving(true);
        setError(null);
        try {
            const payload = { ...assignmentFormToPayload(value), expected_version: expectedVersion };
            if (dialog.mode === 'edit') {
                await updateVehicleServiceEmployee(
                    jobId,
                    value.lineId,
                    dialog.assignmentId,
                    payload,
                );
            } else {
                await createVehicleServiceEmployee(jobId, value.lineId, payload);
            }
            setDialog(null);
            await synchronize();
        } catch (requestError) {
            await handleMutationError(requestError);
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
            await synchronize();
        } catch (requestError) {
            await handleMutationError(requestError);
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
