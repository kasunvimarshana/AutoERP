import { useState } from 'react';
import { Button } from '@/shared/components/Button';
import { LoadingState } from '@/shared/components/LoadingState';
import type { NamedResource } from '@/shared/types/common';
import { humanize } from '@/shared/utils/object';
import type { CommissionAwareVehicleServiceJobLine } from '../../commissionTypes';
import type { VehicleServiceJobLine } from '../../vehicleServiceTypes';
import { EmployeePickerPanel } from './EmployeePickerPanel';
import { formatCommissionSummary, type AssignmentRow } from './assignmentForm';

interface WorkforceGroup {
    key: string;
    title: string;
    subtitle: string;
    lines: VehicleServiceJobLine[];
}

export interface PendingWorkforceAssignment {
    line: VehicleServiceJobLine;
    employee: NamedResource;
}

export function EmployeeAssignmentTable({
    lines,
    loading,
    jobSupervisor,
    assigning,
    pendingEmployees,
    onPendingToggle,
    onAssignSelected,
    onEdit,
    onRemove,
}: {
    lines: VehicleServiceJobLine[];
    loading: boolean;
    jobSupervisor: NamedResource | null;
    assigning: boolean;
    pendingEmployees: Record<number, NamedResource[]>;
    onPendingToggle: (lineId: number, employee: NamedResource) => void;
    onAssignSelected: (assignments: PendingWorkforceAssignment[]) => void;
    onEdit: (row: AssignmentRow) => void;
    onRemove: (row: AssignmentRow) => void;
}) {
    const [pickerLineId, setPickerLineId] = useState<number | null>(null);
    const pickerLine = lines.find((line) => line.id === pickerLineId) ?? null;

    if (loading) return <LoadingState />;
    if (lines.length === 0) {
        return (
            <div className="rounded-xl border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500">
                No service or labour lines are available for workforce assignment.
            </div>
        );
    }

    const selections = lines.flatMap((line): PendingWorkforceAssignment[] => {
        const alreadyAssigned = (line.employee_assignments?.length ?? 0) > 0;
        if (line.uses_job_supervisor === true && !alreadyAssigned && jobSupervisor) {
            return [{ line, employee: jobSupervisor }];
        }

        return (pendingEmployees[line.id] ?? []).map((employee) => ({ line, employee }));
    });
    const assignedIds = pickerLine?.employee_assignments?.map((assignment) => assignment.employee_id) ?? [];
    const pickerSelections = pickerLine ? pendingEmployees[pickerLine.id] ?? [] : [];

    return (
        <div className={pickerLine ? 'grid items-start gap-5 lg:grid-cols-[minmax(0,1fr)_22rem]' : ''}>
            <div className="min-w-0 space-y-5">
                <div className="flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 className="font-semibold text-slate-900">Workforce assignments</h3>
                        <p className="text-sm text-slate-500">Select one or more employees per line, then save all pending assignments together.</p>
                    </div>
                    <Button
                        type="button"
                        loading={assigning}
                        disabled={assigning || selections.length === 0}
                        onClick={() => onAssignSelected(selections)}
                    >
                        Assign selected employees{selections.length > 0 ? ` (${selections.length})` : ''}
                    </Button>
                </div>

                {groupLines(lines).map((group) => (
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
                                    selectedEmployees={line.uses_job_supervisor === true
                                        ? (jobSupervisor ? [jobSupervisor] : [])
                                        : (pendingEmployees[line.id] ?? [])}
                                    isPickerOpen={pickerLineId === line.id}
                                    assigning={assigning}
                                    onOpenPicker={() => setPickerLineId(line.id)}
                                    onPendingRemove={(employee) => onPendingToggle(line.id, employee)}
                                    onEdit={onEdit}
                                    onRemove={onRemove}
                                />
                            ))}
                        </div>
                    </section>
                ))}
            </div>

            {pickerLine && (
                <EmployeePickerPanel
                    lineLabel={pickerLine.description}
                    selectedEmployeeIds={pickerSelections.map((employee) => Number(employee.id))}
                    excludeIds={assignedIds}
                    onClose={() => setPickerLineId(null)}
                    onToggle={(employee) => onPendingToggle(pickerLine.id, employee)}
                />
            )}
        </div>
    );
}

function WorkforceLine({
    line,
    jobSupervisor,
    selectedEmployees,
    isPickerOpen,
    assigning,
    onOpenPicker,
    onPendingRemove,
    onEdit,
    onRemove,
}: {
    line: VehicleServiceJobLine;
    jobSupervisor: NamedResource | null;
    selectedEmployees: NamedResource[];
    isPickerOpen: boolean;
    assigning: boolean;
    onOpenPicker: () => void;
    onPendingRemove: (employee: NamedResource) => void;
    onEdit: (row: AssignmentRow) => void;
    onRemove: (row: AssignmentRow) => void;
}) {
    const assignments = (line.employee_assignments ?? []).map((assignment) => ({ ...assignment, line }));
    const supervisorLine = line.uses_job_supervisor === true;
    const supervisorAssigned = supervisorLine && assignments.length > 0;
    const commission = (line as CommissionAwareVehicleServiceJobLine).commission_default;

    return (
        <div className={`border-l-4 p-4 transition-colors sm:p-5 ${isPickerOpen ? 'border-l-sky-600 bg-sky-50/70' : 'border-l-sky-200 bg-sky-50/25'}`}>
            <div className="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                <div className="flex min-w-0 flex-wrap items-center gap-2">
                    <h4 className="break-words font-semibold text-slate-900">{line.line_number}. {line.description}</h4>
                    {supervisorLine && (
                        <span className="rounded-full bg-sky-100 px-2 py-1 text-xs font-semibold text-sky-800">Supervisor</span>
                    )}
                </div>
                <div className="shrink-0 text-sm text-slate-600">
                    <span className="text-slate-500">Commission</span>{' '}
                    <span className="font-medium text-slate-800">{formatCommissionDefault(commission)}</span>
                </div>
            </div>

            {!supervisorAssigned && (
                <div className="mt-3">
                    {supervisorLine ? (
                        <div className="rounded-lg border border-sky-200 bg-sky-50/70 px-3 py-2 text-sm text-slate-700">
                            {jobSupervisor ? formatNamedResource(jobSupervisor) : <span className="text-amber-700">Select a Job Card supervisor first.</span>}
                        </div>
                    ) : (
                        <button
                            type="button"
                            disabled={assigning}
                            className={`flex min-h-11 w-full items-center justify-between gap-3 rounded-lg border px-3 py-2 text-left text-sm focus:outline-none focus:ring-2 focus:ring-sky-500 disabled:bg-slate-50 ${isPickerOpen ? 'border-sky-400 bg-white ring-1 ring-sky-200' : 'border-sky-200 bg-sky-50/70 hover:border-sky-400 hover:bg-sky-50'}`}
                            onClick={onOpenPicker}
                        >
                            <span className={selectedEmployees.length > 0 ? 'font-medium text-slate-800' : 'text-slate-500'}>
                                {selectedEmployees.length > 0
                                    ? `${selectedEmployees.length} selected · Add employees`
                                    : 'Select or add employees'}
                            </span>
                            <span className="shrink-0 font-semibold text-sky-700">Choose</span>
                        </button>
                    )}

                    {!supervisorLine && selectedEmployees.length > 0 && (
                        <div className="mt-2 flex flex-wrap gap-2" aria-label="Pending employees">
                            {selectedEmployees.map((employee) => (
                                <span key={employee.id} className="inline-flex items-center gap-2 rounded-full bg-amber-100 px-3 py-1 text-xs font-medium text-amber-900 ring-1 ring-inset ring-amber-200">
                                    {formatNamedResource(employee)}
                                    {!supervisorLine && (
                                        <button
                                            type="button"
                                            className="font-bold text-amber-700 hover:text-rose-600"
                                            aria-label={`Remove ${formatNamedResource(employee)}`}
                                            onClick={() => onPendingRemove(employee)}
                                        >
                                            ×
                                        </button>
                                    )}
                                </span>
                            ))}
                        </div>
                    )}
                    {!supervisorLine && selectedEmployees.length > 0 && (
                        <p className="mt-2 text-xs font-medium text-amber-800">
                            {selectedEmployees.length} employee{selectedEmployees.length === 1 ? '' : 's'} selected · Commission is split across assigned employees when you save.
                        </p>
                    )}
                </div>
            )}

            <>
                {assignments.length === 0 && selectedEmployees.length === 0 && (
                    <p className="mt-2 text-xs text-slate-500">No employees assigned yet</p>
                )}
                {assignments.length > 0 && (
                    <div className="mt-3 space-y-2">
                    <p className="text-xs font-medium text-slate-500">Assigned employees · {assignments.length}</p>
                    {assignments.map((row) => (
                        <div key={row.id} className="grid gap-2 rounded-lg border border-slate-200 bg-white p-3 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center">
                            <div className="min-w-0">
                                <div className="flex flex-wrap items-center gap-x-2 gap-y-1">
                                    <strong className="break-words text-sm font-semibold text-slate-900">{row.employee ? formatNamedResource(row.employee) : 'Unavailable employee'}</strong>
                                    <span className="rounded-full bg-white px-2 py-0.5 text-xs text-slate-600">{humanize(row.role_type)}</span>
                                </div>
                                <div className="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-xs text-slate-500">
                                    <span>Hours <strong className="font-medium text-slate-700">{row.assigned_hours}</strong></span>
                                    <span>Commission <strong className="font-medium text-slate-700">{formatCommissionSummary(row)}</strong></span>
                                </div>
                            </div>
                            <div className="flex items-center justify-end gap-1 border-t border-slate-200 pt-2 sm:border-0 sm:pt-0">
                                <button type="button" className="min-h-9 rounded-md px-3 text-sm font-semibold text-sky-700 hover:bg-sky-50 focus:outline-none focus:ring-2 focus:ring-sky-500" onClick={() => onEdit(row)}>Edit</button>
                                <button type="button" className="min-h-9 rounded-md px-3 text-sm font-semibold text-rose-600 hover:bg-rose-50 focus:outline-none focus:ring-2 focus:ring-rose-500" onClick={() => onRemove(row)}>Remove</button>
                            </div>
                        </div>
                    ))}
                    </div>
                )}
            </>
        </div>
    );
}

function groupLines(lines: VehicleServiceJobLine[]): WorkforceGroup[] {
    const groups = new Map<string, WorkforceGroup>();
    for (const line of [...lines].sort(compareLines)) {
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

    return [...groups.values()].sort(
        (left, right) => Number(right.lines.some(isSupervisorLine)) - Number(left.lines.some(isSupervisorLine)),
    );
}

function compareLines(left: VehicleServiceJobLine, right: VehicleServiceJobLine): number {
    return Number(isSupervisorLine(right)) - Number(isSupervisorLine(left)) || left.line_number - right.line_number;
}

function isSupervisorLine(line: VehicleServiceJobLine): boolean {
    return line.uses_job_supervisor === true;
}

function formatNamedResource(resource: NamedResource): string {
    return [resource.code, resource.name].filter(Boolean).join(' - ');
}

function formatCommissionDefault(commission: CommissionAwareVehicleServiceJobLine['commission_default']): string {
    if (!commission || commission.commission_type === 'none') return 'Not set';

    const value = Number(commission.commission_value);
    const formattedValue = Number.isFinite(value)
        ? new Intl.NumberFormat(undefined, { maximumFractionDigits: 2 }).format(value)
        : commission.commission_value;

    return `${humanize(commission.commission_type)} ${formattedValue}${commission.commission_type === 'percentage' ? '%' : ''}`;
}
