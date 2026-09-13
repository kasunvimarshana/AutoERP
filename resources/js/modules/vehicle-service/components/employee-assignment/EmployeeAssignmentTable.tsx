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
    onPendingChange,
    onAssignSelected,
    onEdit,
    onRemove,
}: {
    lines: VehicleServiceJobLine[];
    loading: boolean;
    jobSupervisor: NamedResource | null;
    assigning: boolean;
    pendingEmployees: Record<number, NamedResource>;
    onPendingChange: (lineId: number, employee: NamedResource) => void;
    onAssignSelected: (assignments: PendingWorkforceAssignment[]) => void;
    onEdit: (row: AssignmentRow) => void;
    onRemove: (row: AssignmentRow) => void;
}) {
    const [pickerLineId, setPickerLineId] = useState<number | null>(null);
    const pickerLine = lines.find((line) => line.id === pickerLineId) ?? null;

    if (loading) return <LoadingState />;
    if (lines.length === 0) {
        return <div className="rounded-xl border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500">No service or labour lines are available for workforce assignment.</div>;
    }

    const selections = lines.flatMap((line): PendingWorkforceAssignment[] => {
        const alreadyAssigned = (line.employee_assignments?.length ?? 0) > 0;
        if (line.uses_job_supervisor === true && !alreadyAssigned && jobSupervisor) {
            return [{ line, employee: jobSupervisor }];
        }
        const employee = pendingEmployees[line.id];
        return employee ? [{ line, employee }] : [];
    });
    const assignedIds = pickerLine?.employee_assignments?.map((assignment) => assignment.employee_id) ?? [];

    return (
        <div className={pickerLine ? 'grid items-start gap-5 lg:grid-cols-[minmax(0,1fr)_22rem]' : ''}>
            <div className="min-w-0 space-y-5">
            <div className="flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 className="font-semibold text-slate-900">Workforce assignments</h3>
                    <p className="text-sm text-slate-500">Select employees, then save all pending assignments together.</p>
                </div>
                <Button type="button" loading={assigning} disabled={assigning || selections.length === 0} onClick={() => onAssignSelected(selections)}>
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
                                selectedEmployee={line.uses_job_supervisor === true ? jobSupervisor : (pendingEmployees[line.id] ?? null)}
                                assigning={assigning}
                                onOpenPicker={() => setPickerLineId(line.id)}
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
                    selectedEmployee={pendingEmployees[pickerLine.id] ?? null}
                    excludeIds={assignedIds}
                    onClose={() => setPickerLineId(null)}
                    onSelect={(employee) => onPendingChange(pickerLine.id, employee)}
                />
            )}
        </div>
    );
}

function WorkforceLine({ line, jobSupervisor, selectedEmployee, assigning, onOpenPicker, onEdit, onRemove }: {
    line: VehicleServiceJobLine;
    jobSupervisor: NamedResource | null;
    selectedEmployee: NamedResource | null;
    assigning: boolean;
    onOpenPicker: () => void;
    onEdit: (row: AssignmentRow) => void;
    onRemove: (row: AssignmentRow) => void;
}) {
    const assignments = (line.employee_assignments ?? []).map((assignment) => ({ ...assignment, line }));
    const supervisorLine = line.uses_job_supervisor === true;
    const supervisorAssigned = supervisorLine && assignments.length > 0;
    const commission = (line as CommissionAwareVehicleServiceJobLine).commission_default;

    return (
        <div className="p-4 sm:p-5">
            <div className="flex flex-wrap items-center gap-2">
                <h4 className="font-semibold text-slate-900">{line.line_number}. {line.description}</h4>
                {supervisorLine && <span className="rounded-full bg-sky-100 px-2 py-1 text-xs font-semibold text-sky-800">Supervisor</span>}
            </div>
            <p className="mt-1 text-sm text-slate-500">Commission pool: {commission?.commission_value ?? '0.000000'}</p>

            {!supervisorAssigned && (
                <div className="mt-4">
                    <span className="mb-1 block text-sm font-medium text-slate-700">Employee</span>
                    {supervisorLine ? (
                        <div className="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700">
                            {jobSupervisor ? formatNamedResource(jobSupervisor) : <span className="text-amber-700">Select a Job Card supervisor first.</span>}
                        </div>
                    ) : (
                        <button type="button" disabled={assigning} className="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-left text-sm hover:border-sky-500 disabled:bg-slate-50" onClick={onOpenPicker}>
                            {selectedEmployee ? formatNamedResource(selectedEmployee) : <span className="text-slate-400">Select employee</span>}
                        </button>
                    )}
                    {selectedEmployee && <p className="mt-1 text-xs text-sky-700">Pending assignment</p>}
                </div>
            )}

            {assignments.length === 0 ? (
                <p className="mt-4 rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-500">Not assigned</p>
            ) : (
                <div className="mt-4 space-y-2">
                    {assignments.map((row) => (
                        <div key={row.id} className="flex flex-col gap-3 rounded-lg border border-slate-200 p-3 sm:flex-row sm:items-center sm:justify-between">
                            <div className="grid gap-x-6 gap-y-1 text-sm sm:grid-cols-2 lg:grid-cols-4">
                                <Summary label="Employee" value={row.employee ? formatNamedResource(row.employee) : 'Unavailable employee'} />
                                <Summary label="Designation" value={humanize(row.role_type)} />
                                <Summary label="Hours" value={row.assigned_hours} />
                                <Summary label="Commission" value={formatCommissionSummary(row)} />
                            </div>
                            <div className="flex shrink-0 gap-3">
                                <button type="button" className="font-semibold text-sky-700" onClick={() => onEdit(row)}>Edit</button>
                                <button type="button" className="font-semibold text-rose-600" onClick={() => onRemove(row)}>Remove</button>
                            </div>
                        </div>
                    ))}
                </div>
            )}
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

function Summary({ label, value }: { label: string; value: string }) {
    return <div><span className="text-xs uppercase text-slate-500">{label}</span><strong className="block font-medium text-slate-900">{value}</strong></div>;
}
