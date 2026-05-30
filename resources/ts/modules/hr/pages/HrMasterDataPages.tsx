import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { Button } from '../../../shared/components/ui/Button';
import { Card } from '../../../shared/components/ui/Card';
import { Input } from '../../../shared/components/ui/Input';
import {
    DepartmentForm,
    DepartmentTable,
    DepartmentTreeView,
    DesignationForm,
    DesignationTable,
    EmploymentTypeForm,
    EmploymentTypeTable,
    HrPageHeader,
} from '../components/HrComponents';
import { getDepartmentById, getDesignationById } from '../mock/hrMock';
import { hrApi } from '../services/hrApi';
import type { Department, Designation, EmploymentType } from '../types/hr.types';

export function DepartmentListPage() {
    const [rows, setRows] = useState<Department[]>([]);

    useEffect(() => {
        hrApi.departments.list().then((response) => setRows(response.data));
    }, []);

    return (
        <div className="space-y-6">
            <HrPageHeader actions={<Link to="/hr/departments/new"><Button>New Department</Button></Link>} subtitle="Departments are HR master data used by employee profiles and lookups." title="Departments" />
            <Card className="p-4"><div className="grid gap-3 md:grid-cols-[1fr_160px]"><Input placeholder="Search department..." /><Button variant="secondary">Filter</Button></div></Card>
            <div className="grid gap-5 xl:grid-cols-[1fr_360px]"><DepartmentTable rows={rows} /><DepartmentTreeView rows={rows} /></div>
        </div>
    );
}

export function DepartmentCreatePage() {
    return <div className="space-y-6"><HrPageHeader actions={<><Link to="/hr/departments"><Button variant="secondary">Cancel</Button></Link><Button>Save Department</Button></>} subtitle="Create HR department master data." title="New Department" /><DepartmentForm /></div>;
}

export function DepartmentEditPage() {
    const { id = 'dept-001' } = useParams();
    const [department, setDepartment] = useState<Department>(getDepartmentById(id));

    useEffect(() => {
        hrApi.departments.get(id).then((response) => setDepartment(response.data));
    }, [id]);

    return <div className="space-y-6"><HrPageHeader actions={<Button>Save Changes</Button>} subtitle="Edit department master data and manager reference after backend validation." title={`Edit ${department.name}`} /><DepartmentForm department={department} /></div>;
}

export function DesignationListPage() {
    const [rows, setRows] = useState<Designation[]>([]);

    useEffect(() => {
        hrApi.designations.list().then((response) => setRows(response.data));
    }, []);

    return (
        <div className="space-y-6">
            <HrPageHeader actions={<Link to="/hr/designations/new"><Button>New Designation</Button></Link>} subtitle="Designations support technician, driver, supervisor, and future staff categories." title="Designations" />
            <DesignationTable rows={rows} />
        </div>
    );
}

export function DesignationCreatePage() {
    return <div className="space-y-6"><HrPageHeader actions={<><Link to="/hr/designations"><Button variant="secondary">Cancel</Button></Link><Button>Save Designation</Button></>} subtitle="Create designation master data." title="New Designation" /><DesignationForm /></div>;
}

export function DesignationEditPage() {
    const { id = 'des-001' } = useParams();
    const [designation, setDesignation] = useState<Designation>(getDesignationById(id));

    useEffect(() => {
        hrApi.designations.get(id).then((response) => setDesignation(response.data));
    }, [id]);

    return <div className="space-y-6"><HrPageHeader actions={<Button>Save Changes</Button>} subtitle="Edit designation master data." title={`Edit ${designation.name}`} /><DesignationForm designation={designation} /></div>;
}

export function EmploymentTypeListPage() {
    const [rows, setRows] = useState<EmploymentType[]>([]);

    useEffect(() => {
        hrApi.employmentTypes.list().then((response) => setRows(response.data));
    }, []);

    return (
        <div className="space-y-6">
            <HrPageHeader subtitle="Employment types are reusable HR setup for employee profiles." title="Employment Types" />
            <EmploymentTypeForm />
            <EmploymentTypeTable rows={rows} />
        </div>
    );
}
