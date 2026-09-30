import { useCallback, useState } from 'react';
import { fieldError, type ApiError } from '@/shared/api/apiError';
import { lookupApi } from '@/shared/api/lookupApi';
import { Button } from '@/shared/components/Button';
import { DecimalInput } from '@/shared/components/DecimalInput';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { GenericLookupSelect } from '@/shared/components/GenericLookupSelect';
import { Select } from '@/shared/components/Select';
import type { LookupLoadParams } from '@/shared/types/lookup';
import type { CommissionType, VehicleServiceJobLine } from '../../vehicleServiceTypes';
import type { NamedResource } from '@/shared/types/common';
import {
    applyAssignmentCommissionDefault,
    type AssignmentFormValue,
} from './assignmentForm';

export function EmployeeAssignmentForm({ value, lines, jobSupervisor, error, saving, onSave, onCancel }: {
    value: AssignmentFormValue;
    lines: VehicleServiceJobLine[];
    jobSupervisor: NamedResource | null;
    error: ApiError | null;
    saving: boolean;
    onSave: (value: AssignmentFormValue) => void;
    onCancel: () => void;
}) {
    const [draft, setDraft] = useState(value);
    const searchNonSupervisors = useCallback(
        (params: LookupLoadParams) => lookupApi.availableNonSupervisorEmployees(params),
        [],
    );
    const set = <K extends keyof AssignmentFormValue>(
        key: K,
        next: AssignmentFormValue[K],
    ) => setDraft((current) => ({ ...current, [key]: next }));
    const selectedLine = lines.find((line) => line.id === draft.lineId);
    const submissionEmployee = selectedLine?.uses_job_supervisor === true
        ? jobSupervisor
        : draft.employee;

    return (
        <form
            className="space-y-5"
            onSubmit={(event) => {
                event.preventDefault();
                if (!saving && submissionEmployee) onSave({ ...draft, employee: submissionEmployee });
            }}
        >
            <ErrorAlert error={error} />
            <section className="space-y-4">
                <div>
                    <h3 className="font-semibold text-slate-900">Assignment details</h3>
                    <p className="text-sm text-slate-500">
                        Assign an employee to this labour line. The employee designation is recorded automatically; combo labour commission is fixed by the Job Card snapshot and split across assigned employees.
                    </p>
                </div>
                <div className="grid gap-4 sm:grid-cols-2">
                    <Select
                        label="Service / labour line"
                        value={draft.lineId ?? ''}
                        disabled
                        options={lines.map((line) => ({
                            value: line.id,
                            label: `${line.line_number}. ${line.description}`,
                        }))}
                        onChange={(event) => {
                            const lineId = event.target.value === '' ? null : Number(event.target.value);
                            setDraft((current) => applyAssignmentCommissionDefault(
                                current,
                                lines,
                                lineId,
                                jobSupervisor,
                            ));
                        }}
                    />
                    {selectedLine?.uses_job_supervisor === true ? (
                        <div>
                            <span className="mb-1 block text-sm font-medium text-slate-700">Employee</span>
                            <div className="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700">
                                {jobSupervisor
                                    ? [jobSupervisor.code, jobSupervisor.name].filter(Boolean).join(' - ')
                                    : 'No Job Card supervisor selected'}
                            </div>
                            <p className="mt-1 text-xs text-slate-500">This line always uses the Job Card supervisor.</p>
                        </div>
                    ) : (
                        <GenericLookupSelect
                            label="Employee"
                            value={draft.employee}
                            error={fieldError(error, 'employee_id')}
                            onChange={(employee) => set('employee', employee)}
                            search={searchNonSupervisors}
                            placeholder="Search by code or name"
                            loadOnOpen
                            formatLabel={(employee) => [employee.code, employee.name].filter(Boolean).join(' - ')}
                        />
                    )}
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
                        disabled={draft.commissionLocked}
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
                        disabled={draft.commissionType === 'none' || draft.commissionLocked}
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
                    disabled={draft.lineId === null || !submissionEmployee}
                >
                    Save assignment
                </Button>
            </div>
        </form>
    );
}
