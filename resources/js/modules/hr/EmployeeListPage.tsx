import { useEffect, useState } from 'react';

import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button, LinkButton } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DataTable } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Pagination } from '@/shared/components/Pagination';
import { Select } from '@/shared/components/Select';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { useDebounce } from '@/shared/hooks/useDebounce';
import type { PaginationMeta } from '@/shared/types/pagination';
import { listEmployees, setEmployeeActive } from './hrApi';
import type { EmployeeSummary, HrDepartment, HrDesignation, HrSkill } from './hrTypes';
import { HrDepartmentSelect } from './components/HrDepartmentSelect';
import { HrDesignationSelect } from './components/HrDesignationSelect';
import { HrSkillSelect } from './components/HrSkillSelect';

export default function EmployeeListPage() {
    const [rows, setRows] = useState<EmployeeSummary[]>([]); const [meta, setMeta] = useState<PaginationMeta>(); const [search, setSearch] = useState('');
    const [status, setStatus] = useState(''); const [availability, setAvailability] = useState(''); const [department, setDepartment] = useState<HrDepartment | null>(null);
    const [designation, setDesignation] = useState<HrDesignation | null>(null); const [skill, setSkill] = useState<HrSkill | null>(null); const [page, setPage] = useState(1);
    const [loading, setLoading] = useState(true); const [error, setError] = useState<ApiError | null>(null); const debounced = useDebounce(search);
    useEffect(() => { const c = new AbortController(); setLoading(true); listEmployees({ search: debounced, status: status || undefined, availability_status: availability || undefined, department_id: department?.id, designation_id: designation?.id, skill_id: skill?.id, page, per_page: 25 }, c.signal).then((r) => { setRows(r.data); setMeta(r.meta); setError(null); }).catch((e) => { if (!c.signal.aborted) setError(toApiError(e)); }).finally(() => { if (!c.signal.aborted) setLoading(false); }); return () => c.abort(); }, [availability, debounced, department, designation, page, skill, status]);
    const toggle = (row: EmployeeSummary) => setEmployeeActive(row.id, row.status !== 'active').then((employee) => setRows((current) => current.map((x) => x.id === row.id ? { ...x, status: employee.status, availability_status: employee.availability_status } : x))).catch((e) => setError(toApiError(e)));
    return <div><ContentHeader title="Employees" description="Workforce profiles, qualifications, rates, and assignment availability." actions={<LinkButton to="/hr/employees/create">Create Employee</LinkButton>} />
        <div className="mb-4 grid gap-3 md:grid-cols-3 xl:grid-cols-6"><Input label="Search" value={search} onChange={(e) => { setSearch(e.target.value); setPage(1); }} placeholder="Number, code, name, email, phone" /><Select label="Status" value={status} options={['active', 'inactive', 'on_leave', 'suspended', 'terminated', 'pending_approval'].map(option)} onChange={(e) => { setStatus(e.target.value); setPage(1); }} /><Select label="Availability" value={availability} options={['available', 'assigned', 'on_leave', 'unavailable', 'suspended', 'inactive'].map(option)} onChange={(e) => { setAvailability(e.target.value); setPage(1); }} /><HrDepartmentSelect value={department} onChange={(v) => { setDepartment(v); setPage(1); }} /><HrDesignationSelect value={designation} onChange={(v) => { setDesignation(v); setPage(1); }} /><HrSkillSelect value={skill} onChange={(v) => { setSkill(v); setPage(1); }} /></div>
        <ErrorAlert error={error} />{loading ? <LoadingState label="Loading employees..." /> : <><DataTable rows={rows} rowKey={(row) => row.id} columns={[
            { key: 'employee', header: 'Employee', render: (row) => <div><p className="font-medium">{row.display_name}</p><p className="text-xs text-slate-500">{row.employee_number}{row.code ? ` / ${row.code}` : ''}</p></div> },
            { key: 'department', header: 'Department', render: (row) => row.department?.name ?? '-' }, { key: 'designation', header: 'Designation', render: (row) => row.designation?.name ?? '-' },
            { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.status} /> }, { key: 'availability', header: 'Availability', render: (row) => <StatusBadge status={row.availability_status} /> },
            { key: 'actions', header: '', render: (row) => <div className="flex justify-end gap-2"><LinkButton to={`/hr/employees/${row.id}`} variant="ghost">View</LinkButton><LinkButton to={`/hr/employees/${row.id}/edit`} variant="ghost">Edit</LinkButton>{row.status !== 'terminated' && <Button variant="secondary" onClick={() => void toggle(row)}>{row.status === 'active' ? 'Deactivate' : 'Activate'}</Button>}</div> },
        ]} /><Pagination meta={meta} onPageChange={setPage} /></>}</div>;
}
const option = (value: string) => ({ value, label: value.replaceAll('_', ' ') });
