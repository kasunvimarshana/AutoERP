import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { PreviewPanel } from '../../../shared/components/business/PreviewPanel';
import { Button } from '../../../shared/components/ui/Button';
import { Card } from '../../../shared/components/ui/Card';
import { Input } from '../../../shared/components/ui/Input';
import { Select } from '../../../shared/components/ui/Select';
import { Tabs } from '../../../shared/components/ui/Tabs';
import { getEmployeeById } from '../mock/hrMock';
import { hrApi } from '../services/hrApi';
import {
    EmployeeActivityTimeline,
    EmployeeAddressesTable,
    EmployeeContactsTable,
    EmployeeDocumentsPanel,
    EmployeeForm,
    EmployeeStatusActionPanel,
    EmployeeSummaryCard,
    EmployeeTable,
    EmployeeUserAccessPanel,
    EmploymentDetailsPanel,
    HrPageHeader,
    SalaryProfilePanel,
} from '../components/HrComponents';
import type { Employee } from '../types/hr.types';

export function EmployeeListPage() {
    const [rows, setRows] = useState<Employee[]>([]);

    useEffect(() => {
        hrApi.employees.list().then((response) => setRows(response.data));
    }, []);

    return (
        <div className="space-y-6">
            <HrPageHeader
                actions={<Link to="/hr/employees/new"><Button>New Employee</Button></Link>}
                subtitle="Employees are HR/staff profiles. User access is optional and managed separately."
                title="Employees"
            />
            <Card className="p-4">
                <div className="grid gap-3 md:grid-cols-[1fr_180px_180px_180px_160px]">
                    <Input placeholder="Search employee code, name, email, phone..." />
                    <Select options={[{ label: 'Any department', value: '' }, { label: 'HR', value: 'hr' }, { label: 'Workshop', value: 'workshop' }, { label: 'Rental', value: 'rental' }]} />
                    <Select options={[{ label: 'Any designation', value: '' }, { label: 'Technician', value: 'technician' }, { label: 'Driver', value: 'driver' }, { label: 'Supervisor', value: 'supervisor' }]} />
                    <Select options={[{ label: 'Any status', value: '' }, { label: 'Active', value: 'active' }, { label: 'Inactive', value: 'inactive' }, { label: 'On leave', value: 'on_leave' }, { label: 'Suspended', value: 'suspended' }, { label: 'Terminated', value: 'terminated' }, { label: 'Resigned', value: 'resigned' }, { label: 'Archived', value: 'archived' }]} />
                    <Button variant="secondary">Filter</Button>
                </div>
            </Card>
            <EmployeeTable rows={rows} />
        </div>
    );
}

export function EmployeeCreatePage() {
    return (
        <div className="space-y-6">
            <HrPageHeader
                actions={<><Link to="/hr/employees"><Button variant="secondary">Cancel</Button></Link><Button>Save Employee</Button></>}
                subtitle="Create an employee profile without creating a user account. User access is linked later only if needed."
                title="New Employee"
            />
            <EmployeeForm employee={getEmployeeById('emp-002')} />
        </div>
    );
}

export function EmployeeEditPage() {
    const { id = 'emp-001' } = useParams();
    const [employee, setEmployee] = useState<Employee>(getEmployeeById(id));

    useEffect(() => {
        hrApi.employees.get(id).then((response) => setEmployee(response.data));
    }, [id]);

    return (
        <div className="space-y-6">
            <HrPageHeader
                actions={<><Link to={`/hr/employees/${employee.id}`}><Button variant="secondary">View</Button></Link><Button>Save Changes</Button></>}
                subtitle="Edit employee HR profile. This does not create or modify login identity unless User Access is explicitly used."
                title={`Edit ${employee.displayName}`}
            />
            <EmployeeForm employee={employee} />
        </div>
    );
}

const detailTabs = [
    { label: 'Overview', value: 'overview' },
    { label: 'Contacts', value: 'contacts' },
    { label: 'Addresses', value: 'addresses' },
    { label: 'Employment Details', value: 'employment' },
    { label: 'Documents', value: 'documents' },
    { label: 'User Access', value: 'user' },
    { label: 'Attendance', value: 'attendance' },
    { label: 'Leave', value: 'leave' },
    { label: 'Salary Profile', value: 'salary' },
    { label: 'Activity / Audit', value: 'audit' },
];

export function EmployeeDetailPage() {
    const { id = 'emp-001' } = useParams();
    const [activeTab, setActiveTab] = useState('overview');
    const [employee, setEmployee] = useState<Employee>(getEmployeeById(id));

    useEffect(() => {
        hrApi.employees.get(id).then((response) => setEmployee(response.data));
    }, [id]);

    return (
        <div className="space-y-6">
            <HrPageHeader
                actions={<><Link to={`/hr/employees/${employee.id}/edit`}><Button>Edit</Button></Link><Button variant="blue">Validate Lookup</Button></>}
                subtitle="Employee detail keeps profile, employment, user access, documents, attendance, leave, salary profile, and audit separated."
                title={employee.displayName}
            />
            <Card className="p-5"><Tabs active={activeTab} items={detailTabs} onChange={setActiveTab} /></Card>
            {activeTab === 'overview' ? <div className="grid gap-5 xl:grid-cols-[1fr_360px]"><EmployeeSummaryCard employee={employee} /><EmployeeStatusActionPanel employee={employee} /></div> : null}
            {activeTab === 'contacts' ? <EmployeeContactsTable rows={employee.contacts} /> : null}
            {activeTab === 'addresses' ? <EmployeeAddressesTable rows={employee.addresses} /> : null}
            {activeTab === 'employment' ? <EmploymentDetailsPanel employee={employee} /> : null}
            {activeTab === 'documents' ? <EmployeeDocumentsPanel rows={employee.documents} /> : null}
            {activeTab === 'user' ? <EmployeeUserAccessPanel employee={employee} /> : null}
            {activeTab === 'attendance' ? <PreviewPanel rows={[{ label: 'Attendance total', value: 'Backend calculated' }, { label: 'Overtime', value: 'Backend calculated' }]} status="Attendance" title="Attendance Basics" /> : null}
            {activeTab === 'leave' ? <PreviewPanel rows={[{ label: 'Leave balance', value: 'Backend calculated' }, { label: 'Current requests', value: 'Backend returned' }]} status="Leave" title="Leave Basics" /> : null}
            {activeTab === 'salary' ? <SalaryProfilePanel profile={employee.salaryProfile} /> : null}
            {activeTab === 'audit' ? <EmployeeActivityTimeline rows={employee.audit} /> : null}
        </div>
    );
}
