import { useCallback, useState } from 'react';
import { lookupApi } from '@/shared/api/lookupApi';
import { Button } from '@/shared/components/Button';
import { GenericLookupSelect } from '@/shared/components/GenericLookupSelect';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import type { NamedResource } from '@/shared/types/common';
import type { LookupLoadParams } from '@/shared/types/lookup';
import { humanize } from '@/shared/utils/object';
import type { VehicleServiceJobLine } from '../../vehicleServiceTypes';
import type { CommissionAwareVehicleServiceJobLine } from '../../commissionTypes';
import {
    formatCommissionSummary,
    type AssignmentRow,
} from './assignmentForm';

interface WorkforceGroup {
    key: string;
    title: string;
    subtitle: string;
    lines: VehicleServiceJobLine[];
}

export function EmployeeAssignmentTable({
    lines,
    loading,
    jobSupervisor,
    assigningLineId,
    onAssign,
    onEdit,
    onRemove,
}: {
    lines: VehicleServiceJobLine[];
    loading: boolean;
    jobSupervisor: NamedResource | null;
    assigningLineId: number | null;
    onAssign: (line: VehicleServiceJobLine, employee: NamedResource) => Promise<boolean>;
    onEdit: (row: AssignmentRow) => void;
    onRemove: (row: AssignmentRow) => void;
}) {
    if (loading) return <LoadingState />;

    if (lines.length === 0) {
        return (
            <div className="rounded-xl border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500">
                No service or labour lines are available for workforce assignment.
            </div>
        );
    }

    const groups = groupLines(lines);
    const needsJobSupervisor = lines.some((line) => line.uses_job_supervisor) && !jobSupervisor;

    return (
        <div className="space-y-5">
            {needsJobSupervisor && (
                <div className="rounded-xl border border-amber-200 bg-amber-50 p-4">
                    <p className="font-semibold text-amber-900">Job supervisor required</p>
                    <p className="mt-1 text-sm text-amber-800">
                        Select a supervisor on the Job Card before assigning supervisor labour lines.
                    </p>
                </div>
            )}

            {groups.map((group) => (
                <section key={group.key} className="overflow-hidden rounded-xl border border-slate-200 bg-white">
                    <header className="border-b border-slate-200 bg-slate-50 px-4 py-3 sm:px-5">
                        <h3 className="font-semibold text-slate-900">{group.title}</h3>
                        <p className="text-sm text-slate-500">{group.subtitle}</p>
                    </header>
                    <div className="divide-y divide-slate-200">
                        {group.lines.map((line) => (
                            <WorkforceLine
                                key={line.id}
                                line={line}
                                jobSupervisor={jobSupervisor}
                                assigning={assigningLineId === line.id}
                                assignmentLocked={assigningLineId !== null}
                                onAssign={(employee) => onAssign(line, employee)}
                                onEdit={onEdit}
                                onRemove={onRemove}
                            />
                        ))}
                    </div>
                </section>
            ))}
        </div>
    );
}

function WorkforceLine({
    line,
    jobSupervisor,
    assigning,
    assignmentLocked,
    onAssign,
    onEdit,
    onRemove,
}: {
    line: VehicleServiceJobLine;
    jobSupervisor: NamedResource | null;
    assigning: boolean;
    assignmentLocked: boolean;
    onAssign: (employee: NamedResource) => Promise<boolean>;
    onEdit: (row: AssignmentRow) => void;
    onRemove: (row: AssignmentRow) => void;
}) {
    const [selectedEmployee, setSelectedEmployee] = useState<NamedResource | null>(null);
    const searchEmployees = useCallback(
        (params: LookupLoadParams) => lookupApi.availableNonSupervisorEmployees(params),
        [],
    );
    const assignments = (line.employee_assignments ?? []).map((assignment) => ({ ...assignment, line }));
    const supervisorAssigned = line.uses_job_supervisor === true && assignments.length > 0;
    const commission = (line as CommissionAwareVehicleServiceJobLine).commission_default;
    const assignedEmployeeIds = assignments.map((assignment) => assignment.employee_id);

    const submitAssignment = async (employee: NamedResource | null) => {
        if (!employee || assignmentLocked) return;
        if (await onAssign(employee)) setSelectedEmployee(null);
    };

    return (
        <div className="p-4 sm:p-5">
            <div className="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <div className="flex flex-wrap items-center gap-2">
                        <h4 className="font-semibold text-slate-900">
                            {line.line_number}. {line.description}
                        </h4>
                        {line.uses_job_supervisor === true && (
                            <span className="rounded-full bg-sky-100 px-2 py-1 text-xs font-semibold text-sky-800">
                                Job supervisor
                            </span>
                        )}
                    </div>
                    <p className="mt-1 text-sm text-slate-500">
                        Commission pool: {commission?.commission_value ?? '0.000000'}
                    </p>
                </div>
            </div>

            {line.uses_job_supervisor === true && !supervisorAssigned && (
                <form
                    className="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end"
                    onSubmit={(event) => {
                        event.preventDefault();
                        void submitAssignment(jobSupervisor);
                    }}
                >
                    <div className="min-w-0 flex-1">
                        <Input
                            label="Employee"
                            value={jobSupervisor ? formatNamedResource(jobSupervisor) : ''}
                            placeholder="Select a supervisor on the Job Card"
                            disabled
                            readOnly
                        />
                    </div>
                    <Button
                        type="submit"
                        loading={assigning}
                        disabled={!jobSupervisor || assignmentLocked}
                        className="shrink-0 sm:min-w-24"
                    >
                        Add
                    </Button>
                </form>
            )}

            {line.uses_job_supervisor !== true && (
                <form
                    className="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end"
                    onSubmit={(event) => {
                        event.preventDefault();
                        void submitAssignment(selectedEmployee);
                    }}
                >
                    <div className="min-w-0 flex-1">
                        <GenericLookupSelect
                            label="Employee"
                            value={selectedEmployee}
                            onChange={setSelectedEmployee}
                            search={searchEmployees}
                            formatLabel={formatNamedResource}
                            excludeIds={assignedEmployeeIds}
                            loadOnOpen
                            disabled={assignmentLocked}
                        />
                    </div>
                    <Button
                        type="submit"
                        loading={assigning}
                        disabled={!selectedEmployee || assignmentLocked}
                        className="shrink-0 sm:min-w-24"
                    >
                        Add
                    </Button>
                </form>
            )}

            {assignments.length === 0 ? (
                <p className="mt-4 rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-500">
                    Not assigned
                </p>
            ) : (
                <div className="mt-4 space-y-2">
                    {assignments.map((row) => (
                        <div key={row.id} className="flex flex-col gap-3 rounded-lg border border-slate-200 p-3 sm:flex-row sm:items-center sm:justify-between">
                            <div className="grid gap-x-6 gap-y-1 text-sm sm:grid-cols-2 lg:grid-cols-4">
                                <Summary label="Employee" value={formatEmployee(row)} />
                                <Summary label="Designation" value={humanize(row.role_type)} />
                                <Summary label="Hours" value={row.assigned_hours} />
                                <Summary label="Commission" value={formatCommissionSummary(row)} />
                            </div>
                            <AssignmentActions onEdit={() => onEdit(row)} onRemove={() => onRemove(row)} />
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
}

function groupLines(lines: VehicleServiceJobLine[]): WorkforceGroup[] {
    const groups = new Map<string, WorkforceGroup>();

    for (const line of [...lines].sort((left, right) => left.line_number - right.line_number)) {
        const parent = line.parent_line;
        const key = parent ? `combo-${parent.id}` : 'standalone';
        const group = groups.get(key) ?? {
            key,
            title: parent ? parent.description : 'Standalone service / labour',
            subtitle: parent ? `Combo line ${parent.line_number}` : 'Labour added directly to the Job Card',
            lines: [],
        };
        group.lines.push(line);
        groups.set(key, group);
    }

    return [...groups.values()];
}

function formatEmployee(row: AssignmentRow): string {
    return row.employee ? formatNamedResource(row.employee) : 'Unavailable employee';
}

function formatNamedResource(resource: NamedResource): string {
    return [resource.code, resource.name].filter(Boolean).join(' - ');
}

function AssignmentActions({ onEdit, onRemove }: { onEdit: () => void; onRemove: () => void }) {
    return (
        <div className="flex shrink-0 gap-3">
            <button type="button" className="font-semibold text-sky-700" onClick={onEdit}>
                Edit
            </button>
            <button type="button" className="font-semibold text-rose-600" onClick={onRemove}>
                Remove
            </button>
        </div>
    );
}

function Summary({ label, value }: { label: string; value: string }) {
    return (
        <div>
            <span className="text-xs uppercase text-slate-500">{label}</span>
            <strong className="block font-medium text-slate-900">{value}</strong>
        </div>
    );
}
