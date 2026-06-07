import { useCallback, useState } from 'react';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { GenericLookupSelect } from '@/shared/components/GenericLookupSelect';
import { lookupApi } from '@/shared/api/lookupApi';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Select } from '@/shared/components/Select';
import type { NamedResource } from '@/shared/types/common';
import { useApi } from '@/shared/hooks/useApi';
import { createVehicleServiceEmployee, deleteVehicleServiceEmployee, listEmployeeAssignableLines } from '../vehicleServiceApi';
import type { CommissionType, VehicleServiceEmployeeAssignment, VehicleServiceJobLine } from '../vehicleServiceTypes';

export default function VehicleServiceEmployeeAssignmentTab({ jobId }: { jobId: number }) {
    const result = useApi((signal) => listEmployeeAssignableLines(jobId, signal), [jobId]);
    const [lineId, setLineId] = useState('');
    const [employee, setEmployee] = useState<NamedResource | null>(null);
    const [role, setRole] = useState('technician');
    const [hours, setHours] = useState('0.000000');
    const [rate, setRate] = useState('0.000000');
    const [commissionType, setCommissionType] = useState<CommissionType>('none');
    const [commissionValue, setCommissionValue] = useState('0.000000');
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const search = useCallback(async (query: string, signal: AbortSignal): Promise<NamedResource[]> => {
        return lookupApi.availableEmployees(query, signal);
    }, []);
    const assignments = (result.data ?? []).flatMap((line) => (line.employee_assignments ?? []).map((assignment) => ({ ...assignment, line })));
    type Row = VehicleServiceEmployeeAssignment & { line: VehicleServiceJobLine };
    const columns: DataColumn<Row>[] = [
        { key: 'line', header: 'Line', render: (row) => `${row.line.line_number}. ${row.line.description}` },
        { key: 'employee', header: 'Employee', render: (row) => row.employee?.name ?? 'Unavailable employee' },
        { key: 'role', header: 'Role', render: (row) => row.role_type },
        { key: 'hours', header: 'Hours', render: (row) => row.assigned_hours },
        { key: 'commission', header: 'Commission', render: (row) => `${row.commission_type}: ${row.commission_amount}` },
        { key: 'actions', header: '', render: (row) => <Button type="button" variant="danger" onClick={async () => {
            setError(null);
            try {
                await deleteVehicleServiceEmployee(jobId, row.line.id, row.id);
                result.reload();
            } catch (requestError) {
                setError(toApiError(requestError));
            }
        }}>Remove</Button> },
    ];

    return (
        <div className="space-y-5">
            <ErrorAlert error={error ?? result.error} />
            <form className="grid gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4 md:grid-cols-2 xl:grid-cols-4" onSubmit={async (event) => {
                event.preventDefault();
                if (!lineId || !employee) return;
                setSaving(true);
                setError(null);
                try {
                    await createVehicleServiceEmployee(jobId, Number(lineId), {
                        employee_id: employee.id,
                        role_type: role,
                        assigned_hours: hours,
                        rate,
                        commission_type: commissionType,
                        commission_value: commissionValue,
                    });
                    setEmployee(null);
                    result.reload();
                } catch (requestError) {
                    setError(toApiError(requestError));
                } finally {
                    setSaving(false);
                }
            }}>
                <Select label="Service / labour line" value={lineId} options={(result.data ?? []).map((line) => ({ value: line.id, label: `${line.line_number}. ${line.description}` }))} onChange={(event) => setLineId(event.target.value)} />
                <GenericLookupSelect label="Employee" value={employee} onChange={setEmployee} search={search} formatLabel={(value) => `${value.code ?? ''} ${value.name}`.trim()} />
                <Select label="Role" value={role} options={['technician', 'helper', 'inspector', 'custom'].map((value) => ({ value, label: value }))} onChange={(event) => setRole(event.target.value)} />
                <Input label="Assigned hours" type="number" min="0" step="0.000001" value={hours} onChange={(event) => setHours(event.target.value)} />
                <Input label="Rate" type="number" min="0" step="0.000001" value={rate} onChange={(event) => setRate(event.target.value)} />
                <Select label="Commission" value={commissionType} options={['none', 'fixed', 'percentage'].map((value) => ({ value, label: value }))} onChange={(event) => setCommissionType(event.target.value as CommissionType)} />
                <Input label="Commission value" type="number" min="0" step="0.000001" value={commissionValue} onChange={(event) => setCommissionValue(event.target.value)} />
                <div className="flex items-end"><Button type="submit" loading={saving}>Assign employee</Button></div>
            </form>
            {result.loading ? <LoadingState /> : <DataTable rows={assignments} columns={columns} rowKey={(row) => row.id} emptyMessage="No workforce assignments." />}
        </div>
    );
}
