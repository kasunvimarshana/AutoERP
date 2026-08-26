import type { ApiError } from '@/shared/api/apiError';
import { FormDrawer } from '@/shared/components/Drawer';
import type { VehicleServiceJobLine } from '../../vehicleServiceTypes';
import type { NamedResource } from '@/shared/types/common';
import type {
    AssignmentDialogState,
    AssignmentFormValue,
} from './assignmentForm';
import { EmployeeAssignmentForm } from './EmployeeAssignmentForm';

export function EmployeeAssignmentDialog({ dialog, lines, jobSupervisor, error, saving, onSave, onClose }: {
    dialog: AssignmentDialogState | null;
    lines: VehicleServiceJobLine[];
    jobSupervisor: NamedResource | null;
    error: ApiError | null;
    saving: boolean;
    onSave: (value: AssignmentFormValue) => void;
    onClose: () => void;
}) {
    return (
        <FormDrawer
            open={Boolean(dialog)}
            title="Edit assignment"
            onClose={() => !saving && onClose()}
        >
            {dialog && (
                <EmployeeAssignmentForm
                    key={`edit-${dialog.assignmentId}`}
                    value={dialog.value}
                    lines={lines}
                    jobSupervisor={jobSupervisor}
                    error={error}
                    saving={saving}
                    onCancel={onClose}
                    onSave={onSave}
                />
            )}
        </FormDrawer>
    );
}
