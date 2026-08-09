import { useEffect, useRef, useState } from 'react';
import { useStore } from 'zustand';
import { ApiError, hasFieldError, toApiError } from '@/shared/api/apiError';
import { ConfirmDialog } from '@/shared/components/ConfirmDialog';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { useApi } from '@/shared/hooks/useApi';
import {
    createVehicleServiceEmployee,
    deleteVehicleServiceEmployee,
    getVehicleServiceJob,
    listEmployeeAssignableLines,
    updateVehicleServiceEmployee,
} from '../vehicleServiceApi';
import type { VehicleServiceJobLine } from '../vehicleServiceTypes';
import type { NamedResource } from '@/shared/types/common';
import type { VehicleServiceJobStore, WorkforceSnapshot } from '../state/vehicleServiceJobStore';
import {
    applyAssignmentCommissionDefault,
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
const MISSING_JOB_VERSION_MESSAGE = 'The refreshed service job did not include its row version.';

export default function VehicleServiceEmployeeAssignmentTab({
    jobId,
    expectedVersion,
    onChanged,
    active,
    jobStore,
}: {
    jobId: number;
    expectedVersion: number;
    onChanged: (nextVersion: number) => void;
    active: boolean;
    jobStore: VehicleServiceJobStore;
}) {
    const workforce = useStore(jobStore, (state) => state.workforce);
    const result = useApi(async (signal) => {
        const snapshot = await loadWorkforceSnapshot(jobId, signal);
        jobStore.getState().replaceWorkforce(snapshot);
        onChanged(snapshot.rowVersion);

        return snapshot;
    }, [jobId], true, false);
    const wasActive = useRef(active);
    const [dialog, setDialog] = useState<AssignmentDialogState | null>(null);
    const [removeTarget, setRemoveTarget] = useState<AssignmentRow | null>(null);
    const [saving, setSaving] = useState(false);
    const [assigningLineId, setAssigningLineId] = useState<number | null>(null);
    const [removing, setRemoving] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const reload = result.reload;

    useEffect(() => {
        const becameActive = active && !wasActive.current;
        wasActive.current = active;
        if (becameActive) reload();
    }, [active, reload]);

    const synchronize = async () => {
        const snapshot = await loadWorkforceSnapshot(jobId);
        jobStore.getState().replaceWorkforce(snapshot);
        result.setData(snapshot);
        onChanged(snapshot.rowVersion);
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

    const createAssignment = async (
        line: VehicleServiceJobLine,
        employee: NamedResource,
    ): Promise<boolean> => {
        if (assigningLineId !== null) return false;

        const value = {
            ...applyAssignmentCommissionDefault(
                emptyAssignmentForm(),
                workforce?.lines ?? [],
                line.id,
                workforce?.supervisor ?? null,
            ),
            employee,
        };
        setAssigningLineId(line.id);
        setError(null);
        try {
            await createVehicleServiceEmployee(jobId, line.id, {
                ...assignmentFormToPayload(value),
                expected_version: expectedVersion,
            });
            await synchronize();

            return true;
        } catch (requestError) {
            await handleMutationError(requestError);

            return false;
        } finally {
            setAssigningLineId(null);
        }
    };

    const saveAssignment = async (value: AssignmentFormValue) => {
        if (!dialog || value.lineId === null || !value.employee || saving) return;
        setSaving(true);
        setError(null);
        try {
            const payload = { ...assignmentFormToPayload(value), expected_version: expectedVersion };
            await updateVehicleServiceEmployee(
                jobId,
                value.lineId,
                dialog.assignmentId,
                payload,
            );
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
                loading={result.loading && workforce === null}
                lines={workforce?.lines ?? []}
                jobSupervisor={workforce?.supervisor ?? null}
                assigningLineId={assigningLineId}
                onAssign={createAssignment}
                onEdit={(row) => {
                    setError(null);
                    setDialog({
                        assignmentId: row.id,
                        value: assignmentToForm(row),
                    });
                }}
                onRemove={setRemoveTarget}
            />
            <EmployeeAssignmentDialog
                dialog={dialog}
                lines={workforce?.lines ?? []}
                jobSupervisor={workforce?.supervisor ?? null}
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

async function loadWorkforceSnapshot(jobId: number, signal?: AbortSignal): Promise<WorkforceSnapshot> {
    const [lines, job] = await Promise.all([
        listEmployeeAssignableLines(jobId, signal),
        getVehicleServiceJob(jobId, signal),
    ]);
    if (typeof job.row_version !== 'number') {
        throw new Error(MISSING_JOB_VERSION_MESSAGE);
    }

    return { lines, rowVersion: job.row_version, supervisor: job.supervisor ?? null };
}
