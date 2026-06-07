import { useCallback, useState } from 'react';
import { fieldError, toApiError, type ApiError } from '@/shared/api/apiError';
import { lookupApi } from '@/shared/api/lookupApi';
import { Button } from '@/shared/components/Button';
import { ConfirmDialog } from '@/shared/components/ConfirmDialog';
import { DecimalInput } from '@/shared/components/DecimalInput';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { FormDrawer } from '@/shared/components/Drawer';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { GenericLookupSelect } from '@/shared/components/GenericLookupSelect';
import { LoadingState } from '@/shared/components/LoadingState';
import { Select } from '@/shared/components/Select';
import type { NamedResource } from '@/shared/types/common';
import { useApi } from '@/shared/hooks/useApi';
import { createVehicleServiceEmployee, deleteVehicleServiceEmployee, listEmployeeAssignableLines, updateVehicleServiceEmployee } from '../vehicleServiceApi';
import type { CommissionType, VehicleServiceEmployeeAssignment, VehicleServiceJobLine } from '../vehicleServiceTypes';

type AssignmentRow = VehicleServiceEmployeeAssignment & { line: VehicleServiceJobLine };
type AssignmentDialog =
    | { mode: 'create'; value: AssignmentFormValue }
    | { mode: 'edit'; assignmentId: number; value: AssignmentFormValue };

interface AssignmentFormValue {
    lineId: string;
    employee: NamedResource | null;
    role: string;
    hours: string;
    rate: string;
    commissionType: CommissionType;
    commissionValue: string;
}

export default function VehicleServiceEmployeeAssignmentTab({ jobId }: { jobId: number }) {
    const result = useApi((signal) => listEmployeeAssignableLines(jobId, signal), [jobId]);
    const [dialog, setDialog] = useState<AssignmentDialog | null>(null);
    const [removeTarget, setRemoveTarget] = useState<AssignmentRow | null>(null);
    const [saving, setSaving] = useState(false);
    const [removing, setRemoving] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const assignments = (result.data ?? []).flatMap((line) => (line.employee_assignments ?? []).map((assignment) => ({ ...assignment, line })));

    const saveAssignment = async (value: AssignmentFormValue) => {
        if (!dialog || !value.lineId || !value.employee) return;
        setSaving(true);
        setError(null);
        try {
            const payload = assignmentFormToPayload(value);
            if (dialog.mode === 'edit') {
                await updateVehicleServiceEmployee(jobId, Number(value.lineId), dialog.assignmentId, payload);
            } else {
                await createVehicleServiceEmployee(jobId, Number(value.lineId), payload);
            }
            setDialog(null);
            result.reload();
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setSaving(false);
        }
    };
    const removeAssignment = async (row: AssignmentRow) => {
        setRemoving(true);
        setError(null);
        try {
            await deleteVehicleServiceEmployee(jobId, row.line.id, row.id);
            setRemoveTarget(null);
            result.reload();
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
                onAdd={() => setDialog({ mode: 'create', value: emptyAssignmentForm() })}
                onEdit={(row) => setDialog({ mode: 'edit', assignmentId: row.id, value: assignmentToForm(row) })}
                onRemove={setRemoveTarget}
            />
            <FormDrawer open={Boolean(dialog)} title={dialog?.mode === 'edit' ? 'Edit assignment' : 'Assign employee'} onClose={() => !saving && setDialog(null)}>
                {dialog && (
                    <EmployeeAssignmentForm
                        key={dialog.mode === 'edit' ? `edit-${dialog.assignmentId}` : 'create'}
                        value={dialog.value}
                        lines={result.data ?? []}
                        error={error}
                        saving={saving}
                        onCancel={() => setDialog(null)}
                        onSave={(value) => void saveAssignment(value)}
                    />
                )}
            </FormDrawer>
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

function EmployeeAssignmentTable({ rows, loading, onAdd, onEdit, onRemove }: {
    rows: AssignmentRow[];
    loading: boolean;
    onAdd: () => void;
    onEdit: (row: AssignmentRow) => void;
    onRemove: (row: AssignmentRow) => void;
}) {
    const columns: DataColumn<AssignmentRow>[] = [
        { key: 'line', header: 'Line', render: (row) => `${row.line.line_number}. ${row.line.description}` },
        { key: 'employee', header: 'Employee', render: (row) => row.employee?.name ?? 'Unavailable employee' },
        { key: 'role', header: 'Role', render: (row) => row.role_type },
        { key: 'hours', header: 'Hours', render: (row) => row.assigned_hours, className: 'tabular-nums' },
        { key: 'rate', header: 'Rate', render: (row) => row.rate, className: 'tabular-nums' },
        { key: 'commission', header: 'Commission', render: formatCommissionSummary },
        { key: 'actions', header: 'Actions', className: 'text-right', render: (row) => <AssignmentActions onEdit={() => onEdit(row)} onRemove={() => onRemove(row)} /> },
    ];

    return (
        <div className="space-y-3">
            <div className="flex justify-end"><Button type="button" onClick={onAdd}>Assign employee</Button></div>
            {loading ? <LoadingState /> : <DataTable rows={rows} columns={columns} rowKey={(row) => row.id} emptyMessage="No workforce assignments. Click Assign employee to start." mobileSummary={(row) => row.employee?.name ?? 'Unavailable employee'} mobileDetails={(row) => <AssignmentMobileDetails row={row} />} mobileActions={(row) => <AssignmentActions onEdit={() => onEdit(row)} onRemove={() => onRemove(row)} />} />}
        </div>
    );
}

function EmployeeAssignmentForm({ value, lines, error, saving, onSave, onCancel }: {
    value: AssignmentFormValue;
    lines: VehicleServiceJobLine[];
    error: ApiError | null;
    saving: boolean;
    onSave: (value: AssignmentFormValue) => void;
    onCancel: () => void;
}) {
    const [draft, setDraft] = useState(value);
    const search = useCallback(async (query: string, signal: AbortSignal): Promise<NamedResource[]> => lookupApi.availableEmployees(query, signal), []);
    const set = <K extends keyof AssignmentFormValue>(key: K, next: AssignmentFormValue[K]) => setDraft((current) => ({ ...current, [key]: next }));

    return (
        <form className="space-y-5" onSubmit={(event) => { event.preventDefault(); onSave(draft); }}>
            <ErrorAlert error={error} />
            <section className="space-y-4">
                <div>
                    <h3 className="font-semibold text-slate-900">Basic Details</h3>
                    <p className="text-sm text-slate-500">Select the service line, employee, role, hours, and rate.</p>
                </div>
                <div className="grid gap-4 sm:grid-cols-2">
                    <Select label="Service / labour line" value={draft.lineId} options={lines.map((line) => ({ value: line.id, label: `${line.line_number}. ${line.description}` }))} error={fieldError(error, 'line_id')} onChange={(event) => set('lineId', event.target.value)} />
                    <GenericLookupSelect label="Employee" value={draft.employee} onChange={(employee) => set('employee', employee)} search={search} formatLabel={(employee) => `${employee.code ?? ''} ${employee.name}`.trim()} />
                    <Select label="Role" value={draft.role} options={['technician', 'helper', 'inspector', 'custom'].map((role) => ({ value: role, label: role }))} error={fieldError(error, 'role_type')} onChange={(event) => set('role', event.target.value)} />
                    <DecimalInput label="Assigned hours" value={draft.hours} error={fieldError(error, 'assigned_hours')} onChange={(event) => set('hours', event.target.value)} />
                    <DecimalInput label="Rate" value={draft.rate} error={fieldError(error, 'rate')} onChange={(event) => set('rate', event.target.value)} />
                    <Select label="Commission" value={draft.commissionType} options={['none', 'fixed', 'percentage'].map((type) => ({ value: type, label: type }))} error={fieldError(error, 'commission_type')} onChange={(event) => set('commissionType', event.target.value as CommissionType)} />
                    <DecimalInput label="Commission value" value={draft.commissionValue} error={fieldError(error, 'commission_value')} onChange={(event) => set('commissionValue', event.target.value)} />
                </div>
            </section>
            <div className="flex justify-end gap-2">
                <Button type="button" variant="secondary" onClick={onCancel}>Cancel</Button>
                <Button type="submit" loading={saving} disabled={!draft.lineId || !draft.employee}>Save assignment</Button>
            </div>
        </form>
    );
}

function emptyAssignmentForm(): AssignmentFormValue {
    return {
        lineId: '',
        employee: null,
        role: 'technician',
        hours: '0.000000',
        rate: '0.000000',
        commissionType: 'none',
        commissionValue: '0.000000',
    };
}

function assignmentToForm(row: AssignmentRow): AssignmentFormValue {
    return {
        lineId: String(row.line.id),
        employee: row.employee ?? null,
        role: row.role_type,
        hours: row.assigned_hours,
        rate: row.rate,
        commissionType: row.commission_type,
        commissionValue: row.commission_value,
    };
}

function assignmentFormToPayload(value: AssignmentFormValue): Record<string, unknown> {
    return {
        employee_id: value.employee?.id,
        role_type: value.role,
        assigned_hours: value.hours,
        rate: value.rate,
        commission_type: value.commissionType,
        commission_value: value.commissionValue,
    };
}

function formatCommissionSummary(row: AssignmentRow): string {
    if (row.commission_type === 'none') return 'None';
    return `${row.commission_type}: ${row.commission_amount}`;
}

function AssignmentActions({ onEdit, onRemove }: { onEdit: () => void; onRemove: () => void }) {
    return <div className="flex justify-end gap-3"><button type="button" className="font-semibold text-sky-700" onClick={onEdit}>Edit assignment</button><button type="button" className="font-semibold text-rose-600" onClick={onRemove}>Remove assignment</button></div>;
}

function AssignmentMobileDetails({ row }: { row: AssignmentRow }) {
    return <div className="grid grid-cols-2 gap-2"><Summary label="Line" value={`${row.line.line_number}. ${row.line.description}`} /><Summary label="Role" value={row.role_type} /><Summary label="Hours" value={row.assigned_hours} /><Summary label="Commission" value={formatCommissionSummary(row)} /></div>;
}

function Summary({ label, value }: { label: string; value: string }) {
    return <div><span className="text-xs uppercase text-slate-500">{label}</span><strong className="block text-slate-900">{value}</strong></div>;
}
