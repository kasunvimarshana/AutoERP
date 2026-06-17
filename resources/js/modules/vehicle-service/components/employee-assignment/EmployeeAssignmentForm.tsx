import { useCallback, useState } from 'react';
import { fieldError, type ApiError } from '@/shared/api/apiError';
import { lookupApi } from '@/shared/api/lookupApi';
import { Button } from '@/shared/components/Button';
import { DecimalInput } from '@/shared/components/DecimalInput';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { GenericLookupSelect } from '@/shared/components/GenericLookupSelect';
import { Select } from '@/shared/components/Select';
import type { NamedResource } from '@/shared/types/common';
import type { LookupLoadParams } from '@/shared/types/lookup';
import type { CommissionType, VehicleServiceJobLine } from '../../vehicleServiceTypes';
import type { AssignmentFormValue } from './assignmentForm';

export function EmployeeAssignmentForm({ value, mode, lines, error, saving, onSave, onCancel }: {
    value: AssignmentFormValue;
    mode: 'create' | 'edit';
    lines: VehicleServiceJobLine[];
    error: ApiError | null;
    saving: boolean;
    onSave: (value: AssignmentFormValue) => void;
    onCancel: () => void;
}) {
    const [draft, setDraft] = useState(value);
    const search = useCallback(
        (params: LookupLoadParams) => lookupApi.availableEmployees(params),
        [],
    );
    const set = <K extends keyof AssignmentFormValue>(
        key: K,
        next: AssignmentFormValue[K],
    ) => setDraft((current) => ({ ...current, [key]: next }));

    return (
        <form
            className="space-y-5"
            onSubmit={(event) => {
                event.preventDefault();
                if (!saving) onSave(draft);
            }}
        >
            <ErrorAlert error={error} />
            <section className="space-y-4">
                <div>
                    <h3 className="font-semibold text-slate-900">Assignment details</h3>
                    <p className="text-sm text-slate-500">
                        Select the service line, employee, role, hours, rate, and commission.
                    </p>
                </div>
                <div className="grid gap-4 sm:grid-cols-2">
                    <Select
                        label="Service / labour line"
                        value={draft.lineId ?? ''}
                        disabled={mode === 'edit'}
                        options={lines.map((line) => ({
                            value: line.id,
                            label: `${line.line_number}. ${line.description}`,
                        }))}
                        onChange={(event) => set(
                            'lineId',
                            event.target.value === '' ? null : Number(event.target.value),
                        )}
                    />
                    <GenericLookupSelect
                        label="Employee"
                        value={draft.employee}
                        error={fieldError(error, 'employee_id')}
                        onChange={(employee) => set('employee', employee)}
                        search={search}
                        formatLabel={(employee) =>
                            [employee.code, employee.name].filter(Boolean).join(' - ')}
                    />
                    <Select
                        label="Role"
                        value={draft.role}
                        options={['technician', 'helper', 'inspector', 'custom'].map((role) => ({
                            value: role,
                            label: role,
                        }))}
                        error={fieldError(error, 'role_type')}
                        onChange={(event) => set('role', event.target.value)}
                    />
                    <DecimalInput
                        label="Assigned hours"
                        value={draft.hours}
                        error={fieldError(error, 'assigned_hours')}
                        onChange={(event) => set('hours', event.target.value)}
                    />
                    <DecimalInput
                        label="Rate"
                        value={draft.rate}
                        error={fieldError(error, 'rate')}
                        onChange={(event) => set('rate', event.target.value)}
                    />
                    <Select
                        label="Commission"
                        value={draft.commissionType}
                        options={['none', 'fixed', 'percentage'].map((type) => ({
                            value: type,
                            label: type,
                        }))}
                        error={fieldError(error, 'commission_type')}
                        onChange={(event) => set(
                            'commissionType',
                            event.target.value as CommissionType,
                        )}
                    />
                    <DecimalInput
                        label="Commission value"
                        value={draft.commissionValue}
                        error={fieldError(error, 'commission_value')}
                        onChange={(event) => set('commissionValue', event.target.value)}
                    />
                    <Select
                        label="Status"
                        value={draft.status}
                        options={['assigned', 'completed', 'cancelled'].map((status) => ({
                            value: status,
                            label: status,
                        }))}
                        error={fieldError(error, 'status')}
                        onChange={(event) => set(
                            'status',
                            event.target.value as AssignmentFormValue['status'],
                        )}
                    />
                </div>
            </section>
            <div className="flex justify-end gap-2">
                <Button type="button" variant="secondary" onClick={onCancel}>Cancel</Button>
                <Button
                    type="submit"
                    loading={saving}
                    disabled={draft.lineId === null || !draft.employee}
                >
                    Save assignment
                </Button>
            </div>
        </form>
    );
}
