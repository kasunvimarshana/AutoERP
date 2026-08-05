import { Button } from '@/shared/components/Button';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { LoadingState } from '@/shared/components/LoadingState';
import { humanize } from '@/shared/utils/object';
import {
    formatCommissionSummary,
    type AssignmentRow,
} from './assignmentForm';

export function EmployeeAssignmentTable({ rows, loading, onAdd, onEdit, onRemove }: {
    rows: AssignmentRow[];
    loading: boolean;
    onAdd: () => void;
    onEdit: (row: AssignmentRow) => void;
    onRemove: (row: AssignmentRow) => void;
}) {
    const columns: DataColumn<AssignmentRow>[] = [
        { key: 'line', header: 'Line', render: (row) => `${row.line.line_number}. ${row.line.description}` },
        { key: 'employee', header: 'Employee', render: (row) => formatEmployee(row) },
        { key: 'designation', header: 'Designation', render: (row) => humanize(row.role_type) },
        { key: 'hours', header: 'Hours', render: (row) => row.assigned_hours, className: 'tabular-nums' },
        { key: 'rate', header: 'Rate', render: (row) => row.rate, className: 'tabular-nums' },
        { key: 'commission', header: 'Commission', render: formatCommissionSummary },
        { key: 'status', header: 'Status', render: (row) => row.status },
        {
            key: 'actions',
            header: 'Actions',
            className: 'text-right',
            render: (row) => (
                <AssignmentActions onEdit={() => onEdit(row)} onRemove={() => onRemove(row)} />
            ),
        },
    ];

    return (
        <div className="space-y-3">
            <div className="flex justify-end">
                <Button type="button" onClick={onAdd}>Assign employee</Button>
            </div>
            {loading
                ? <LoadingState />
                : (
                    <DataTable
                        rows={rows}
                        columns={columns}
                        rowKey={(row) => row.id}
                        emptyMessage="No workforce assignments. Click Assign employee to start."
                        mobileSummary={formatEmployee}
                        mobileDetails={(row) => <AssignmentMobileDetails row={row} />}
                        mobileActions={(row) => (
                            <AssignmentActions onEdit={() => onEdit(row)} onRemove={() => onRemove(row)} />
                        )}
                    />
                )}
        </div>
    );
}

function formatEmployee(row: AssignmentRow): string {
    if (!row.employee) return 'Unavailable employee';
    return [row.employee.code, row.employee.name].filter(Boolean).join(' - ');
}

function AssignmentActions({ onEdit, onRemove }: { onEdit: () => void; onRemove: () => void }) {
    return (
        <div className="flex justify-end gap-3">
            <button type="button" className="font-semibold text-sky-700" onClick={onEdit}>
                Edit assignment
            </button>
            <button type="button" className="font-semibold text-rose-600" onClick={onRemove}>
                Remove assignment
            </button>
        </div>
    );
}

function AssignmentMobileDetails({ row }: { row: AssignmentRow }) {
    return (
        <div className="grid grid-cols-2 gap-2">
            <Summary label="Line" value={`${row.line.line_number}. ${row.line.description}`} />
            <Summary label="Designation" value={humanize(row.role_type)} />
            <Summary label="Hours" value={row.assigned_hours} />
            <Summary label="Commission" value={formatCommissionSummary(row)} />
            <Summary label="Status" value={row.status} />
        </div>
    );
}

function Summary({ label, value }: { label: string; value: string }) {
    return (
        <div>
            <span className="text-xs uppercase text-slate-500">{label}</span>
            <strong className="block text-slate-900">{value}</strong>
        </div>
    );
}
