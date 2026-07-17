import { useEffect, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import { AccessDeniedPage } from '@/app/errors/AccessDeniedPage';
import { hasPermission } from '@/modules/auth/accessControl';
import { useAuth } from '@/modules/auth/AuthProvider';
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
import { HrDepartmentSelect } from './components/HrDepartmentSelect';
import { HrDesignationSelect } from './components/HrDesignationSelect';
import { HrMasterDataPanel } from './components/HrMasterDataPanel';
import { HrSkillSelect } from './components/HrSkillSelect';
import { listEmployees, setEmployeeActive } from './hrApi';
import type { HrMasterKind } from './hrMasterApi';
import { hrPermissions } from './hrPermissions';
import type { EmployeeSummary, HrDepartment, HrDesignation, HrSkill } from './hrTypes';

const HR_MASTER_VIEWS: readonly HrMasterKind[] = [
    'departments',
    'designations',
    'employment-types',
    'skills',
    'certifications',
    'licenses',
];

export default function EmployeeListPage() {
    const auth = useAuth();
    const [searchParams] = useSearchParams();
    const requestedView = searchParams.get('view');
    const masterKind = HR_MASTER_VIEWS.find((view) => view === requestedView) ?? null;
    const canViewEmployees = hasPermission(auth, hrPermissions.employeesView);
    const canViewMasterData = hasPermission(auth, hrPermissions.masterDataView)
        || hasPermission(auth, hrPermissions.masterDataManage);

    if (masterKind) {
        return canViewMasterData ? <HrMasterDataPanel kind={masterKind} /> : <AccessDeniedPage />;
    }
    if (!canViewEmployees) return <AccessDeniedPage />;

    return <EmployeeListContent />;
}

function EmployeeListContent() {
    const auth = useAuth();
    const canCreate = hasPermission(auth, hrPermissions.employeesCreate);
    const canUpdate = hasPermission(auth, hrPermissions.employeesUpdate);
    const [rows, setRows] = useState<EmployeeSummary[]>([]);
    const [meta, setMeta] = useState<PaginationMeta>();
    const [search, setSearch] = useState('');
    const [status, setStatus] = useState('');
    const [availability, setAvailability] = useState('');
    const [department, setDepartment] = useState<HrDepartment | null>(null);
    const [designation, setDesignation] = useState<HrDesignation | null>(null);
    const [skill, setSkill] = useState<HrSkill | null>(null);
    const [page, setPage] = useState(1);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<ApiError | null>(null);
    const debounced = useDebounce(search);

    useEffect(() => {
        const controller = new AbortController();
        queueMicrotask(() => {
            if (!controller.signal.aborted) setLoading(true);
        });
        void listEmployees({
            search: debounced,
            status: status || undefined,
            availability_status: availability || undefined,
            department_id: department?.id,
            designation_id: designation?.id,
            skill_id: skill?.id,
            page,
            per_page: 25,
        }, controller.signal)
            .then((response) => {
                if (controller.signal.aborted) return;
                setRows(response.data);
                setMeta(response.meta);
                setError(null);
            })
            .catch((requestError) => {
                if (!controller.signal.aborted) setError(toApiError(requestError));
            })
            .finally(() => {
                if (!controller.signal.aborted) setLoading(false);
            });
        return () => controller.abort();
    }, [availability, debounced, department, designation, page, skill, status]);

    const toggle = (row: EmployeeSummary) => setEmployeeActive(row.id, row.status !== 'active', row.row_version)
        .then((employee) => setRows((current) => current.map((candidate) => candidate.id === row.id ? {
            ...candidate,
            status: employee.status,
            availability_status: employee.availability_status,
            row_version: employee.row_version,
        } : candidate)))
        .catch((requestError) => setError(toApiError(requestError)));

    return <div>
        <ContentHeader title="Employees" description="Workforce profiles, qualifications, rates, and assignment availability." actions={canCreate ? <LinkButton to="/hr/employees/create">Create Employee</LinkButton> : undefined} />
        <div className="mb-4 grid gap-3 md:grid-cols-3 xl:grid-cols-6">
            <Input label="Search" value={search} onChange={(event) => { setSearch(event.target.value); setPage(1); }} placeholder="Number, code, name, email, phone" />
            <Select label="Status" value={status} options={['active', 'inactive', 'on_leave', 'suspended', 'terminated', 'pending_approval'].map(option)} onChange={(event) => { setStatus(event.target.value); setPage(1); }} />
            <Select label="Availability" value={availability} options={['available', 'assigned', 'on_leave', 'unavailable', 'suspended', 'inactive'].map(option)} onChange={(event) => { setAvailability(event.target.value); setPage(1); }} />
            <HrDepartmentSelect value={department} onChange={(value) => { setDepartment(value); setPage(1); }} />
            <HrDesignationSelect value={designation} onChange={(value) => { setDesignation(value); setPage(1); }} />
            <HrSkillSelect value={skill} onChange={(value) => { setSkill(value); setPage(1); }} />
        </div>
        <ErrorAlert error={error} />
        {loading ? <LoadingState label="Loading employees..." /> : <>
            <DataTable rows={rows} rowKey={(row) => row.id} columns={[
                { key: 'employee', header: 'Employee', render: (row) => <div><p className="font-medium">{row.display_name}</p><p className="text-xs text-slate-500">{row.employee_number}{row.code ? ` / ${row.code}` : ''}</p></div> },
                { key: 'department', header: 'Department', render: (row) => row.department?.name ?? '-' },
                { key: 'designation', header: 'Designation', render: (row) => row.designation?.name ?? '-' },
                { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.status} /> },
                { key: 'availability', header: 'Availability', render: (row) => <StatusBadge status={row.availability_status} /> },
                { key: 'actions', header: '', render: (row) => <div className="flex justify-end gap-2"><LinkButton to={`/hr/employees/${row.id}`} variant="ghost">View</LinkButton>{canUpdate && <LinkButton to={`/hr/employees/${row.id}/edit`} variant="ghost">Edit</LinkButton>}{canUpdate && row.status !== 'terminated' && <Button variant="secondary" onClick={() => void toggle(row)}>{row.status === 'active' ? 'Deactivate' : 'Activate'}</Button>}</div> },
            ]} />
            <Pagination meta={meta} onPageChange={setPage} />
        </>}
    </div>;
}

const option = (value: string) => ({ value, label: value.replaceAll('_', ' ') });
