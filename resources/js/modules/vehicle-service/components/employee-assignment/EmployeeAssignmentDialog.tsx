import type { ApiError } from '@/shared/api/apiError';
import { FormDrawer } from '@/shared/components/Drawer';
import type { VehicleServiceJobLine } from '../../vehicleServiceTypes';
import type {
    AssignmentDialogState,
    AssignmentFormValue,
} from './assignmentForm';
import { EmployeeAssignmentForm } from './EmployeeAssignmentForm';

export function EmployeeAssignmentDialog({ dialog, lines, error, saving, onSave, onClose }: {
    dialog: AssignmentDialogState | null;
    lines: VehicleServiceJobLine[];
    error: ApiError | null;
    saving: boolean;
    onSave: (value: AssignmentFormValue) => void;
    onClose: () => void;
}) {
    return (
        <FormDrawer
            open={Boolean(dialog)}
            title={dialog?.mode === 'edit' ? 'Edit assignment' : 'Assign employee'}
            onClose={() => !saving && onClose()}
        >
            {dialog && (
                <EmployeeAssignmentForm
                    key={dialog.mode === 'edit' ? `edit-${dialog.assignmentId}` : 'create'}
                    value={dialog.value}
                    mode={dialog.mode}
                    lines={lines}
                    error={error}
                    saving={saving}
                    onCancel={onClose}
                    onSave={onSave}
                />
            )}
        </FormDrawer>
    );
}
